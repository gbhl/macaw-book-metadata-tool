<?php
defined('BASEPATH') OR exit('No direct script access allowed');

use chillerlan\QRCode\QRCode as ChillerlanQRCode;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QROutputInterface;

/**
 * QrCode Library
 *
 * Thin CodeIgniter 3 wrapper around chillerlan/php-qrcode that generates
 * QR codes as inline SVG strings.
 *
 * @package Macaw
 */
class QrCode
{
    /**
     * Generate an inline SVG QR code for the given data string.
     *
     * @param  string $data     The data to encode.
     * @param  int    $cellSize Pixels (SVG units) per module. Default 4.
     * @param  int    $margin   Quiet-zone size in modules. Default 4.
     * @return string           Inline SVG markup.
     */
    public static function svg($data, $cellSize = 4, $margin = 4)
    {
        $options = new QROptions([
            'outputType'      => QROutputInterface::MARKUP_SVG,
            'eccLevel'        => EccLevel::M,
            'addQuietzone'    => true,
            'quietzoneSize'   => (int) $margin,
            'scale'           => (int) $cellSize,
            // Return raw SVG markup rather than a base64 data URI.
            'outputBase64'    => false,
            // Suppress the XML declaration so the result is embeddable inline.
            'svgAddXmlHeader' => false,
        ]);

        return (new ChillerlanQRCode($options))->render($data);
    }
}
