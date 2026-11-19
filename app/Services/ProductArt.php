<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Generates a poster-style SVG for a product (sunburst, halftone dots,
 * a silhouette for the category motif and a slanted name plate), so the
 * shop needs no image files. Deterministic: the same product always gets
 * the same artwork.
 */
class ProductArt
{
    private const RED = '#e60012';
    private const BLACK = '#0d0d0d';
    private const WHITE = '#f5f5f0';

    public function render(int $id, string $name, string $motif, bool $soldOut = false): string
    {
        $seed = crc32($name . $id);
        $dark = $seed % 3 === 0;
        $bg = $dark ? self::BLACK : self::RED;
        $ray = $dark ? '#1f1f1f' : '#c4000f';
        $dot = $dark ? '#3a3a3a' : '#ff3b3b';
        $fill = $dark ? self::RED : self::BLACK;
        $tilt = (($seed >> 3) % 15) - 7;
        $uid = 'p' . $id;

        $rays = $this->sunburst(200, 190, 300, 18, $ray, ($seed >> 5) % 20);
        $motifSvg = $this->motif($motif, $fill, $bg);
        $label = htmlspecialchars(mb_strtoupper(mb_substr($name, 0, 22)), ENT_QUOTES | ENT_XML1, 'UTF-8');
        $stamp = $soldOut
            ? '<g transform="rotate(-18 200 200)"><rect x="70" y="165" width="260" height="70" fill="none" stroke="' . self::WHITE . '" stroke-width="8"/>'
              . '<text x="200" y="216" font-family="Anton, Impact, sans-serif" font-size="46" fill="' . self::WHITE . '" text-anchor="middle" letter-spacing="4">SOLD OUT</text></g>'
            : '';

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 400 400" role="img" aria-label="{$label}">
  <defs>
    <pattern id="dots-{$uid}" width="14" height="14" patternUnits="userSpaceOnUse" patternTransform="rotate(30)">
      <circle cx="7" cy="7" r="2.6" fill="{$dot}"/>
    </pattern>
    <clipPath id="clip-{$uid}"><rect width="400" height="400"/></clipPath>
  </defs>
  <g clip-path="url(#clip-{$uid})">
    <rect width="400" height="400" fill="{$bg}"/>
    {$rays}
    <rect width="400" height="400" fill="url(#dots-{$uid})" opacity="0.55"/>
    <polygon points="0,330 400,280 400,400 0,400" fill="{$fill}" opacity="0.9"/>
    <g transform="rotate({$tilt} 200 190)">
      <g transform="translate(10 10)" opacity="0.9">{$this->motif($motif, self::WHITE, self::WHITE)}</g>
      {$motifSvg}
    </g>
    <polygon points="250,40 262,72 296,72 269,92 279,124 250,104 221,124 231,92 204,72 238,72" fill="{$this->starColor($dark)}" transform="rotate(12 250 82) translate(70 -15) scale(0.8)"/>
    <g transform="rotate(-4 200 352)">
      <polygon points="18,322 390,308 382,372 10,384" fill="{$this->plateColor($dark)}"/>
      <text x="200" y="360" font-family="Anton, 'Bebas Neue', Impact, sans-serif" font-size="30" fill="{$this->plateText($dark)}" text-anchor="middle" letter-spacing="2">{$label}</text>
    </g>
    {$stamp}
  </g>
</svg>
SVG;
    }

    private function starColor(bool $dark): string
    {
        return $dark ? self::WHITE : self::BLACK;
    }

    private function plateColor(bool $dark): string
    {
        return $dark ? self::WHITE : self::BLACK;
    }

    private function plateText(bool $dark): string
    {
        return $dark ? self::BLACK : self::WHITE;
    }

    private function sunburst(int $cx, int $cy, int $radius, int $count, string $color, int $offsetDeg): string
    {
        $out = '';
        $step = 360 / $count;
        for ($i = 0; $i < $count; $i += 2) {
            $a1 = deg2rad($i * $step + $offsetDeg);
            $a2 = deg2rad(($i + 1) * $step + $offsetDeg);
            $out .= sprintf(
                '<polygon points="%d,%d %.1f,%.1f %.1f,%.1f" fill="%s"/>',
                $cx, $cy,
                $cx + cos($a1) * $radius, $cy + sin($a1) * $radius,
                $cx + cos($a2) * $radius, $cy + sin($a2) * $radius,
                $color
            );
        }
        return $out;
    }

    /** Silhouettes for each category motif, centred around (200, 190). */
    private function motif(string $motif, string $fill, string $hole): string
    {
        return match ($motif) {
            'mask' => <<<SVG
                <path fill-rule="evenodd" fill="{$fill}" d="M70 175 Q110 105 200 140 Q290 105 330 175 Q315 250 245 238 Q215 232 200 205 Q185 232 155 238 Q85 250 70 175 Z
                  M115 178 Q140 150 172 172 Q150 200 115 178 Z M228 172 Q260 150 285 178 Q250 200 228 172 Z"/>
                <path d="M200 140 L208 95 L192 95 Z" fill="{$fill}"/>
            SVG,
            'coat' => <<<SVG
                <path fill="{$fill}" d="M150 75 L250 75 L300 120 L335 300 L285 312 L270 195 L268 325 L132 325 L130 195 L115 312 L65 300 L100 120 Z"/>
                <path fill="none" stroke="{$hole}" stroke-width="5" d="M150 75 L200 160 L250 75 M200 160 L200 325"/>
                <circle cx="215" cy="200" r="6" fill="{$hole}"/><circle cx="215" cy="245" r="6" fill="{$hole}"/>
            SVG,
            'gadget' => <<<SVG
                <rect x="135" y="70" width="130" height="240" rx="20" fill="{$fill}"/>
                <rect x="148" y="95" width="104" height="180" fill="{$hole}" opacity="0.92"/>
                <polygon fill="{$fill}" points="200,130 211,162 245,162 218,182 228,214 200,194 172,214 182,182 155,162 189,162"/>
                <circle cx="200" cy="292" r="8" fill="{$hole}"/>
            SVG,
            'book' => <<<SVG
                <path fill="{$fill}" d="M80 115 L200 140 L320 115 L320 300 L200 325 L80 300 Z"/>
                <path fill="none" stroke="{$hole}" stroke-width="6" d="M200 140 L200 325 M105 150 L180 165 M105 180 L180 195 M220 165 L295 150 M220 195 L295 180"/>
            SVG,
            'cup' => <<<SVG
                <path fill="{$fill}" d="M115 150 L270 150 L255 305 Q250 322 232 322 L153 322 Q135 322 130 305 Z"/>
                <path fill="none" stroke="{$fill}" stroke-width="18" d="M265 185 Q320 185 312 235 Q305 275 258 270"/>
                <path fill="none" stroke="{$fill}" stroke-width="8" stroke-linecap="round" d="M160 130 Q145 105 165 85 M195 130 Q180 100 200 75 M230 130 Q215 105 235 85"/>
                <rect x="140" y="190" width="105" height="14" fill="{$hole}" opacity="0.9"/>
            SVG,
            default => <<<SVG
                <polygon fill="{$fill}" points="200,60 236,150 330,155 257,215 282,305 200,255 118,305 143,215 70,155 164,150"/>
            SVG,
        };
    }
}
