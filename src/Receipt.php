<?php

declare(strict_types=1);

namespace JakubBoucek\EscPos;

class Receipt
{
    private const C_ESC = "\x1b";
    private const C_GS = "\x1d";

    /** @var string[] */
    private $buffer = [];

    public function __construct()
    {
        $this->init();
    }

    private function init(): void
    {
        // ESC @
        $this->buffer = [self::C_ESC . '@'];
    }

    private function buff(string $data): void
    {
        $this->buffer[] = $data;
    }

    private function finalize(): void
    {
        $last = end($this->buffer);
        //                    LF   cut
        if (in_array($last, ["\n", self::C_GS . "\x56\x42\x03"], true) === false) {
            $this->lf();
        }
    }

    public function lf(): self
    {
        // LF
        $this->buff("\n");
        return $this;
    }

    public function feed(int $lines = 1): self
    {
        // ESC d <n>
        $this->buff(
            self::C_ESC . 'd'
            . chr($lines)
        );
        return $this;
    }

    public function cut(bool $fullCut = false): self
    {
        // GS V <n>
        $this->buff(
            self::C_GS . 'V'
            . chr($fullCut ? 0 : 1)
        );
        return $this;
    }

    public function feedCut(int $feedBefore = 3, bool $fullCut = false): self
    {
        // GS V <m> <n>
        $this->buff(
            self::C_GS . 'V'
            . ($fullCut ? "\x41" : "\x42")
            . chr($feedBefore)
        );
        return $this;
    }

    public function write(string $data, bool $alreadyEncoded = false): self
    {
        if ($alreadyEncoded === false) {
            $data = $this->encodeOutput($data);
        }

        $this->buff($data);
        return $this;
    }

    public function writeLf(string $data, bool $alreadyEncoded = false): self
    {
        $this->write($data, $alreadyEncoded);
        $this->lf();
        return $this;
    }

    public function logo(int $kc1, int $kc2, int $sizeX = 1, int $sizeY = 1): self
    {
        $this->validateLogoKeyCode($kc1);
        $this->validateLogoKeyCode($kc2);

        //GS (L 6 0 48 69 <kc1> <kc2> <x> <y>
        $this->buff(
            self::C_GS
            . '(L'
            . "\x06\x00\x30\x45"
            . chr($kc1) . chr($kc2)
            . chr($sizeX) . chr($sizeY)
        );
        return $this;
    }

    public function ean13(string $data, int $width = 3, int $height = 42, int $text = 0, int $font = 0): self
    {
        $this->buff(
            self::C_GS . 'w' . chr($width)
            . self::C_GS . 'h' . chr($height)
            . self::C_GS . 'H' . chr($text)
            . self::C_GS . 'f' . chr($font)
            . self::C_GS . 'k' . "\x02" . $data . "\x00"
        );
        return $this;
    }

    public function code128(string $data, int $width = 3, int $height = 42, int $text = 0, int $font = 0): self
    {
        $barData = "{B{1$data";
        $this->buff(
            self::C_GS . 'w' . chr($width)
            . self::C_GS . 'h' . chr($height)
            . self::C_GS . 'H' . chr($text)
            . self::C_GS . 'f' . chr($font)
            . self::C_GS . "\x6bI" . chr(strlen($barData)) . $barData
        );
        return $this;
    }

    public function line(int $type = 0): self
    {
        $chars = [
            // \xc4 = Em dash
            0 => "\xc4",
            1 => '-',
            2 => '- ',
        ];
        // 48 = chars to receipt width
        return $this->writeLf(str_repeat($chars[$type], $type === 2 ? (48 / 2) : 48), true);
    }

    public function left(): self
    {
        $this->buff(self::C_ESC . "\x61\x00");
        return $this;
    }

    public function center(): self
    {
        $this->buff(self::C_ESC . "\x61\x01");
        return $this;
    }

    public function right(): self
    {
        $this->buff(self::C_ESC . "\x61\x02");
        return $this;
    }

    public function bold(): self
    {
        $this->buff(self::C_ESC . "\x45\x01");
        return $this;
    }

    public function unbold(): self
    {
        $this->buff(self::C_ESC . "\x45\x00");
        return $this;
    }

    public function italic(): self
    {
        $this->buff(self::C_ESC . "\x34\x01");
        return $this;
    }

    public function unitalic(): self
    {
        $this->buff(self::C_ESC . "\x34\x00");
        return $this;
    }

    public function underline(): self
    {
        $this->buff(self::C_ESC . "\x2d\x01");
        return $this;
    }

    public function ununderline(): self
    {
        $this->buff(self::C_ESC . "\x2d\x00");
        return $this;
    }

    public function fontA(): self
    {
        $this->buff(self::C_ESC . "\x4d\x00");
        return $this;
    }

    public function fontB(): self
    {
        $this->buff(self::C_ESC . "\x4d\x01");
        return $this;
    }

    public function fontSet(int $doubleW = 0, int $doubleH = 0, int $fontB = 0): self
    {
        $code = ($doubleW ? 32 : 0) + ($doubleH ? 16 : 0) + ($fontB ? 1 : 0);
        $this->buff(self::C_ESC . "\x21" . chr($code));
        return $this;
    }

    public function strlen(string $data, bool $alreadyEncoded = false): int
    {
        if ($alreadyEncoded) {
            return strlen($data);
        }

        return strlen($this->encodeOutput($data));
    }

    private function encodeOutput(string $data): string
    {
        return iconv('UTF-8', 'cp852//IGNORE', $data);
    }

    private function validateLogoKeyCode(int $keyCode): void
    {
        if ($keyCode < 32 || $keyCode > 126) {
            throw new InvalidKeyCodeException("Graphic key code expected between 32 and 126, $keyCode given.");
        }
    }

    public function test(): string
    {
        $output = '';
        foreach ($this->buffer as $value) {
            for ($i = 0, $len = strlen($value); $i < $len; $i++) {
                $output .= "\\x" . sprintf('%02s', dechex(ord($value[$i])));
            }
            $output .= "\n";
        }
        return $output;
    }

    public function compile(): string
    {
        $this->finalize();
        return implode('', $this->buffer);
    }

    public function __toString(): string
    {
        return $this->compile();
    }
}
