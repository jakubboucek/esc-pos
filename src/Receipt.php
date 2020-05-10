<?php

namespace JakubBoucek\EscPos;

class Receipt
{
    private $buffer = [];

    public function __construct()
    {
    }

    public function __toString()
    {
        $this->finalize();
        return join("", $this->buffer);
    }

    public function test()
    {
        $output = "";
        foreach ($this->buffer as $value) {
            for ($i = 0; $i < strlen($value); $i++) {
                $output .= "\\x" . sprintf('%02s', dechex(ord($value[$i])));
            }
            $output .= "\n";
        }
        return $output;
    }

    private function resetBuff()
    {
        $this->buffer = [];
    }

    private function buff($data)
    {
        $this->buffer[] = $data;
    }

    public function finalize()
    {
        $last = end($this->buffer);
        //                           LF      cut
        if (!in_array($last, ["\x0a", "\x1d\x56\x42\x03"])) {
            $this->lf();
        }
    }

    public function init()
    {
        $this->resetBuff();
        //           ESC @
        $this->buff("\x1b\x40");
    }

    public function lf()
    {
        //           LF
        $this->buff("\x0a");
    }

    public function feed($lines = 1)
    {
        $n = chr($lines);
        //           ESC d n
        $this->buff("\x1bd$n");
    }

    public function cut($fullCut = false)
    {
        $n = chr($fullCut ? 0 : 1);
        //           GS  V   n
        $this->buff("\x1d\x56$n");
    }

    public function feedCut($feedBefore = 3, $fullCut = false)
    {
        $m = $fullCut ? "\x41" : "\x42";
        $n = chr($feedBefore);

        //           GS  V     m  n
        $this->buff("\x1d\x56" . $m . $n);
    }

    public function encodeOutput($data)
    {
        return iconv("UTF-8", "ISO-8859-2//IGNORE", $data);
    }

    public function write($data, $encoded = false)
    {
        if ($encoded) {
            $this->buff($data);
        } else {
            $this->buff($this->encodeOutput($data));
        }
    }

    public function strlen($data, $encoded = false)
    {
        if ($encoded) {
            return strlen($data);
        } else {
            return strlen($this->encodeOutput($data));
        }
    }

    public function writeLf($data, $encoded = false)
    {
        $this->write($data, $encoded);
        $this->lf();
    }

    public function logo($kc1, $kc2, $sizeX = 1, $sizeY = 1)
    {
        $this->validateLogoKc($kc1);
        $this->validateLogoKc($kc2);

        $chKc1 = chr($kc1);
        $chKc2 = chr($kc2);
        $chSizeX = chr($sizeX);
        $chSizeY = chr($sizeY);
        //           GS  (L 6  0   48  69      kc1      kc2      x          y
        $this->buff("\x1d(L\x06\x00\x30\x45" . $chKc1 . $chKc2 . $chSizeX . $chSizeY);
    }

    private function validateLogoKc($kc)
    {
        if ($kc < 32 || $kc > 126) {
            throw new InvalidKeyCodeException("Graphic key code expected between 32 and 126, $kc given.");
        }
    }

    public function ean13($data, $width = 3, $height = 42, $text = 0, $font = 0)
    {
        $this->buff("\x1dw" . chr($width));
        $this->buff("\x1dh" . chr($height));
        $this->buff("\x1dH" . chr($text));
        $this->buff("\x1df" . chr($font));
        // $barData = "{B{1$data";
        // $len = strlen($barData);
        $this->buff("\x1dk\x02" . $data . "\x00");
    }

    public function code128($data, $width = 3, $height = 42, $text = 0, $font = 0)
    {
        $this->buff("\x1dw" . chr($width));
        $this->buff("\x1dh" . chr($height));
        $this->buff("\x1dH" . chr($text));
        $this->buff("\x1df" . chr($font));
        $barData = "{B{1$data";
        $len = strlen($barData);
        $this->buff("\x1d\x6bI" . chr($len) . $barData);
    }

    public function left()
    {
        $this->buff("\x1b\x61\x00");
    }

    public function center()
    {
        $this->buff("\x1b\x61\x01");
    }

    public function right()
    {
        $this->buff("\x1b\x61\x02");
    }

    public function bold()
    {
        $this->buff("\x1b\x45\x01");
    }

    public function unbold()
    {
        $this->buff("\x1b\x45\x00");
    }

    public function fontA()
    {
        $this->buff("\x1b\x4d\x00");
    }

    public function fontB()
    {
        $this->buff("\x1b\x4d\x01");
    }

    public function fontSet($doubleW = 0, $doubleH = 0, $fontB = 0)
    {
        $code = ($doubleW ? 32 : 0) + ($doubleH ? 16 : 0) + ($fontB ? 1 : 0);
        $this->buff("\x1b\x21" . chr($code));
    }
}
