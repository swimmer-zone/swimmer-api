<?php

namespace Swimmer\Utils\Getid3;

/**
 * @author James Heinrich <info@getid3.org>
 * @see https://github.com/JamesHeinrich/getid3
 * @see https://www.getid3.org
 * @see http://getid3.sourceforge.net
 */
class Library
{
	/**
	 * @param int $num
	 * @return bool
	 */
	public static function intValueSupported(int $num): bool
	{
		// Check if integers are 64-bit
		static $hasINT64 = null;
		if ($hasINT64 === null) { // 10x faster than is_null()
			$hasINT64 = is_int(pow(2, 31)); // 32-bit int are limited to (2^31)-1
			if (!$hasINT64 && !defined('PHP_INT_MIN')) {
				define('PHP_INT_MIN', ~PHP_INT_MAX);
			}
		}
		// If integers are 64-bit - no other check required
		if ($hasINT64 || ($num <= PHP_INT_MAX && $num >= PHP_INT_MIN)) {
			return true;
		}
		return false;
	}

	/**
	 * @param int $seconds
	 * @return string
	 */
	public static function PlaytimeString(int $seconds): string
	{
		$sign = ($seconds < 0) ? '-' : '';
		$seconds = round(abs($seconds));
		$H = (int) floor( $seconds                            / 3600);
		$M = (int) floor(($seconds - (3600 * $H)            ) /   60);
		$S = (int) round( $seconds - (3600 * $H) - (60 * $M)        );
		return $sign . ($H ? $H . ':' : '') . ($H ? str_pad($M, 2, '0', STR_PAD_LEFT) : intval($M)) . ':' . str_pad($S, 2, 0, STR_PAD_LEFT);
	}

	/**
	 * @param string $in_charset
	 * @param string $out_charset
	 * @param string $string
	 *
	 * @return string
	 * @throws Exception
	 */
	public static function iconv_fallback(string $in_charset, string $out_charset, string $string): string
	{
		if ($in_charset == $out_charset) {
			return $string;
		}

		// mb_convert_encoding() available
		if (function_exists('mb_convert_encoding')) {
			if (strtoupper($in_charset) == 'UTF-16' && substr($string, 0, 2) != "\xFE\xFF" && substr($string, 0, 2) != "\xFF\xFE") {
				// If BOM missing, mb_convert_encoding will mishandle the conversion, assume UTF-16BE and prepend appropriate BOM
				$string = "\xFF\xFE".$string;
			}
			if (strtoupper($in_charset) == 'UTF-16' && strtoupper($out_charset) == 'UTF-8') {
				if ($string == "\xFF\xFE" || $string == "\xFE\xFF") {
					// If string consists of only BOM, mb_convert_encoding will return the BOM unmodified
					return '';
				}
			}
			if ($converted_string = @mb_convert_encoding($string, $out_charset, $in_charset)) {
				switch ($out_charset) {
					case 'ISO-8859-1':
						$converted_string = rtrim($converted_string, "\x00");
						break;
				}
				return $converted_string;
			}
			return $string;

		// iconv() available
		} elseif (function_exists('iconv')) {
			if ($converted_string = @iconv($in_charset, $out_charset . '//TRANSLIT', $string)) {
				switch ($out_charset) {
					case 'ISO-8859-1':
						$converted_string = rtrim($converted_string, "\x00");
						break;
				}
				return $converted_string;
			}

			// iconv() may sometimes fail with "illegal character in input string" error message
			// and return an empty string, but returning the unconverted string is more useful
			return $string;
		}

		throw new Exception('PHP does not has mb_convert_encoding() or iconv() support - cannot convert from ' . $in_charset . ' to ' . $out_charset);
	}

	/**
	 * @param mixed $data
	 * @param string $charset
	 * @return mixed
	 */
	public static function recursiveMultiByteCharString2HTML($data, string $charset = 'ISO-8859-1')
	{
		if (is_string($data)) {
			return self::MultiByteCharString2HTML($data, $charset);
		} elseif (is_array($data)) {
			$return_data = [];
			foreach ($data as $key => $value) {
				$return_data[$key] = self::recursiveMultiByteCharString2HTML($value, $charset);
			}
			return $return_data;
		}
		// integer, float, objects, resources, etc
		return $data;
	}

	/**
	 * @param string|int|float $string
	 * @param string $charset
	 *
	 * @return string
	 */
	public static function MultiByteCharString2HTML($string, string $charset = 'ISO-8859-1'): string
	{
		$string = (string)$string; // in case trying to pass a numeric (float, int) string, would otherwise return an empty string
		$HTMLstring = '';

		switch (strtolower($charset)) {
			case '1251':
			case '1252':
			case '866':
			case '932':
			case '936':
			case '950':
			case 'big5':
			case 'big5-hkscs':
			case 'cp1251':
			case 'cp1252':
			case 'cp866':
			case 'euc-jp':
			case 'eucjp':
			case 'gb2312':
			case 'ibm866':
			case 'iso-8859-1':
			case 'iso-8859-15':
			case 'iso8859-1':
			case 'iso8859-15':
			case 'koi8-r':
			case 'koi8-ru':
			case 'koi8r':
			case 'shift_jis':
			case 'sjis':
			case 'win-1251':
			case 'windows-1251':
			case 'windows-1252':
				$HTMLstring = htmlentities($string, ENT_COMPAT, $charset);
				break;

			case 'utf-8':
				$strlen = strlen($string);
				for ($i = 0; $i < $strlen; $i++) {
					$char_ord_val = ord($string[$i]);
					$charval = 0;
					if ($char_ord_val < 0x80) {
						$charval = $char_ord_val;
					} elseif ((($char_ord_val & 0xF0) >> 4) == 0x0F  &&  $i+3 < $strlen) {
						$charval  = (($char_ord_val & 0x07) << 18);
						$charval += ((ord($string[++$i]) & 0x3F) << 12);
						$charval += ((ord($string[++$i]) & 0x3F) << 6);
						$charval +=  (ord($string[++$i]) & 0x3F);
					} elseif ((($char_ord_val & 0xE0) >> 5) == 0x07  &&  $i+2 < $strlen) {
						$charval  = (($char_ord_val & 0x0F) << 12);
						$charval += ((ord($string[++$i]) & 0x3F) << 6);
						$charval +=  (ord($string[++$i]) & 0x3F);
					} elseif ((($char_ord_val & 0xC0) >> 6) == 0x03  &&  $i+1 < $strlen) {
						$charval  = (($char_ord_val & 0x1F) << 6);
						$charval += (ord($string[++$i]) & 0x3F);
					}
					if (($charval >= 32) && ($charval <= 127)) {
						$HTMLstring .= htmlentities(chr($charval));
					} else {
						$HTMLstring .= '&#'.$charval.';';
					}
				}
				break;

			case 'utf-16le':
				for ($i = 0; $i < strlen($string); $i += 2) {
					$charval = self::LittleEndian2Int(substr($string, $i, 2));
					if (($charval >= 32) && ($charval <= 127)) {
						$HTMLstring .= chr($charval);
					} else {
						$HTMLstring .= '&#'.$charval.';';
					}
				}
				break;

			case 'utf-16be':
				for ($i = 0; $i < strlen($string); $i += 2) {
					$charval = self::BigEndian2Int(substr($string, $i, 2));
					if (($charval >= 32) && ($charval <= 127)) {
						$HTMLstring .= chr($charval);
					} else {
						$HTMLstring .= '&#'.$charval.';';
					}
				}
				break;

			default:
				$HTMLstring = 'ERROR: Character set "' . $charset . '" not supported in MultiByteCharString2HTML()';
				break;
		}
		return $HTMLstring;
	}

	/**
	 * @param float $amplitude
	 * @return float
	 */
	public static function RGADamplitude2dB(float $amplitude): float
	{
		return 20 * log10($amplitude);
	}

	/**
	 * @param string $path
	 * @return float|bool
	 */
	public static function getFileSizeSyscall(string $path)
	{
		$filesize = false;

		$commandline = 'ls -l ' . escapeshellarg($path) . ' | awk \'{print $5}\'';

		if (isset($commandline)) {
			$output = trim(`$commandline`);
			if (ctype_digit($output)) {
				$filesize = (float) $output;
			}
		}
		return $filesize;
	}

	/**
	 * Workaround for Bug #37268 (https://bugs.php.net/bug.php?id=37268)
	 *
	 * @param string $path A path.
	 * @param string $suffix If the name component ends in suffix this will also be cut off
	 * @return string
	 */
	public static function mb_basename(string $path, $suffix = null): string
	{
		$splited = preg_split('#/#', rtrim($path, '/ '));
		return substr(basename('X' . $splited[count($splited) - 1], $suffix), 1);
	}

	/**
	 * @param int|float $floatnum
	 * @return int|float
	 */
	private static function CastAsInt($floatnum)
	{
		// Convert to float if not already
		$floatnum = (float)$floatnum;

		// Convert a float to type int, only if possible
		if (self::trunc($floatnum) == $floatnum) {
			// It's not floating point
			if (self::intValueSupported($floatnum)) {
				// It's within int range
				$floatnum = (int) $floatnum;
			}
		}
		return $floatnum;
	}

	/**
	 * @param string $byteword
	 * @param bool   $signed
	 * @return int|float|false
	 */
	private static function LittleEndian2Int(string $byteword, bool $signed = false)
	{
		return self::BigEndian2Int(strrev($byteword), false, $signed);
	}

	/**
	 * @param string $byteword
	 * @param bool $synchsafe
	 * @param bool $signed
	 * @return int|float|false
	 * @throws Exception
	 */
	private static function BigEndian2Int(string $byteword, bool $synchsafe = false, bool $signed = false)
	{
		$intvalue = 0;
		$bytewordlen = strlen($byteword);
		if ($bytewordlen == 0) {
			return false;
		}
		for ($i = 0; $i < $bytewordlen; $i++) {
			if ($synchsafe) {
				// Disregard MSB, effectively 7-bit bytes
				$intvalue += (ord($byteword[$i]) & 0x7F) * pow(2, ($bytewordlen - 1 - $i) * 7);
			} else {
				$intvalue += ord($byteword[$i]) * pow(256, ($bytewordlen - 1 - $i));
			}
		}
		if ($signed && !$synchsafe) {
			// Syncsafe ints are not allowed to be signed
			if ($bytewordlen <= PHP_INT_SIZE) {
				$signMaskBit = 0x80 << (8 * ($bytewordlen - 1));
				if ($intvalue & $signMaskBit) {
					$intvalue = 0 - ($intvalue & ($signMaskBit - 1));
				}
			} else {
				throw new Exception('ERROR: Cannot have signed integers larger than ' . (8 * PHP_INT_SIZE) . '-bits (' . strlen($byteword) . ') in self::BigEndian2Int()');
			}
		}
		return self::CastAsInt($intvalue);
	}

	/**
	 * Truncates a floating-point number at the decimal point
	 * @param float $floatnumber
	 * @return float|int returns int (if possible, otherwise float)
	 */
	private static function trunc(float $floatnumber)
	{
		if ($floatnumber >= 1) {
			$truncatednumber = floor($floatnumber);
		} elseif ($floatnumber <= -1) {
			$truncatednumber = ceil($floatnumber);
		} else {
			$truncatednumber = 0;
		}
		if (self::intValueSupported($truncatednumber)) {
			$truncatednumber = (int) $truncatednumber;
		}
		return $truncatednumber;
	}
}
