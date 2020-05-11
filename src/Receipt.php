<?php

namespace JakubBoucek\EscPos;

class Receipt
{
    const C_ESC = "\x1b";
    const C_GS = "\x1d";

    private $buffer = [];

    public function __construct()
    {
        $this->init();
    }

    private function init()
    {
        // ESC @
        $this->buffer = [self::C_ESC . '@'];
    }

    private function buff($data)
    {
        $this->buffer[] = $data;
    }

    private function finalize()
    {
        $last = end($this->buffer);
        //                     LF      cut
        if (!in_array($last, ["\n", self::C_GS . "\x56\x42\x03"], true)) {
            $this->lf();
        }
    }

    public function lf()
    {
        // LF
        $this->buff("\n");
        return $this;
    }

    public function feed($lines = 1)
    {
        // ESC d <n>
        $this->buff(
            self::C_ESC . 'd'
            . chr($lines)
        );
        return $this;
    }

    public function cut($fullCut = false)
    {
        // GS V <n>
        $this->buff(
            self::C_GS . "V"
            . chr($fullCut ? 0 : 1)
        );
        return $this;
    }

    public function feedCut($feedBefore = 3, $fullCut = false)
    {
        // GS V <m> <n>
        $this->buff(
            self::C_GS . 'V'
            . ($fullCut ? "\x41" : "\x42")
            . chr($feedBefore)
        );
        return $this;
    }

    public function write($data, $alreadyEncoded = false)
    {
        if ($alreadyEncoded !== true) {
            $data = $this->encodeOutput($data);
        }
        $this->buff($data);
        return $this;
    }

    public function writeLf($data, $encoded = false)
    {
        $this->write($data, $encoded);
        $this->lf();
        return $this;
    }

    public function logo($kc1, $kc2, $sizeX = 1, $sizeY = 1)
    {
        $this->validateLogoKeyCode($kc1);
        $this->validateLogoKeyCode($kc2);

        //GS (L 6 0 48 69 <kc1> <kc2> <x> <y>
        $this->buff(
            self::C_GS
            . "(L"
            . "\x06\x00\x30\x45"
            . chr($kc1) . chr($kc2)
            . chr($sizeX) . chr($sizeY)
        );
        return $this;
    }

    public function ean13($data, $width = 3, $height = 42, $text = 0, $font = 0)
    {
        $this->buff(
            self::C_GS . "w" . chr($width)
            . self::C_GS . "h" . chr($height)
            . self::C_GS . "H" . chr($text)
            . self::C_GS . "f" . chr($font)
            . self::C_GS . "k" . "\x02" . $data . "\x00"
        );
        return $this;
    }

    public function code128($data, $width = 3, $height = 42, $text = 0, $font = 0)
    {
        $barData = "{B{1$data";
        $this->buff(
            self::C_GS . "w" . chr($width)
            . self::C_GS . "h" . chr($height)
            . self::C_GS . "H" . chr($text)
            . self::C_GS . "f" . chr($font)
            . self::C_GS . "\x6bI" . chr(strlen($barData)) . $barData
        );
        return $this;
    }

    public function left()
    {
        $this->buff(self::C_ESC . "\x61\x00");
        return $this;
    }

    public function center()
    {
        $this->buff(self::C_ESC . "\x61\x01");
        return $this;
    }

    public function right()
    {
        $this->buff(self::C_ESC . "\x61\x02");
        return $this;
    }

    public function bold()
    {
        $this->buff(self::C_ESC . "\x45\x01");
        return $this;
    }

    public function unbold()
    {
        $this->buff(self::C_ESC . "\x45\x00");
        return $this;
    }

    public function fontA()
    {
        $this->buff(self::C_ESC . "\x4d\x00");
        return $this;
    }

    public function fontB()
    {
        $this->buff(self::C_ESC . "\x4d\x01");
        return $this;
    }

    public function fontSet($doubleW = 0, $doubleH = 0, $fontB = 0)
    {
        $code = ($doubleW ? 32 : 0) + ($doubleH ? 16 : 0) + ($fontB ? 1 : 0);
        $this->buff(self::C_ESC . "\x21" . chr($code));
        return $this;
    }

    public function strlen($data, $encoded = false)
    {
        if ($encoded) {
            return strlen($data);
        }

        return strlen($this->encodeOutput($data));
    }

    private function encodeOutput($data)
    {
        return iconv("UTF-8", "cp852//IGNORE", $data);
    }

    private function validateLogoKeyCode($keyCode)
    {
        if ($keyCode < 32 || $keyCode > 126) {
            throw new InvalidKeyCodeException("Graphic key code expected between 32 and 126, $keyCode given.");
        }
    }

    public function test()
    {
        $output = "";
        foreach ($this->buffer as $value) {
            for ($i = 0, $len = strlen($value); $i < $len; $i++) {
                $output .= "\\x" . sprintf('%02s', dechex(ord($value[$i])));
            }
            $output .= "\n";
        }
        return $output;
    }

    public function __toString()
    {
        $this->finalize();
        return implode("", $this->buffer);
    }
}
