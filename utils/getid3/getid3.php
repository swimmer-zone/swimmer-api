<?php

namespace Swimmer\Utils\Getid3;

/**
 * @author James Heinrich <info@getid3.org>
 * @see https://github.com/JamesHeinrich/getid3
 * @see https://www.getid3.org
 * @see http://getid3.sourceforge.net
 */
class Getid3
{
	/** @var string CASE SENSITIVE! - i.e. (must be supported by iconv()). Examples:  ISO-8859-1  UTF-8  UTF-16  UTF-16BE */
	private $encoding = 'UTF-8';

	/** @var string Should always be 'ISO-8859-1', but some tags may be written in other encodings such as 'EUC-CN' or 'CP1252' */
	public $encoding_id3v1 = 'ISO-8859-1';

	/** @var bool ID3v1 should always be 'ISO-8859-1', but some tags may be written in other encodings such as 'Windows-1251' or 'KOI8-R'. If true attempt to detect these encodings, but may return incorrect values for some tags actually in ISO-8859-1 encoding */
	public $encoding_id3v1_autodetect = false;

	/** @var string Filename of file being analysed */
	public $filename;

	/** @var resource Filepointer to file being analysed */
	public $fp;

	/** @var array Result array */
	public $info;

	/** @var int */
	public $memory_limit = 0;

	/** @var string */
	private $startup_error = '';

	/**@var string */
	private $startup_warning = '';

	const VERSION = '1.10.0';
	const FREAD_BUFFER_SIZE = 32768;

	/**
	 * @return void
	 */
	public function __construct() 
	{
		$memoryLimit = ini_get('memory_limit');
		if (preg_match('#([0-9]+) ?M#i', $memoryLimit, $matches)) {
			// Could be stored as "16M" rather than 16777216 for example
			$memoryLimit = $matches[1] * 1048576;
		} elseif (preg_match('#([0-9]+) ?G#i', $memoryLimit, $matches)) { // The 'G' modifier is available since PHP 5.1.0
			// Could be stored as "2G" rather than 2147483648 for example
			$memoryLimit = $matches[1] * 1073741824;
		}
		$this->memory_limit = $memoryLimit;

		if ($this->memory_limit <= 0) {
			// memory limits probably disabled
		} elseif ($this->memory_limit <= 4194304) {
			$this->startup_error .= 'PHP has less than 4MB available memory and will very likely run out. Increase memory_limit in php.ini'."\n";
		} elseif ($this->memory_limit <= 12582912) {
			$this->startup_warning .= 'PHP has less than 12MB available memory and might run out if all modules are loaded. Increase memory_limit in php.ini'."\n";
		}

		// Check safe_mode off
		if (preg_match('#(1|ON)#i', ini_get('safe_mode'))) {
			$this->warning('WARNING: Safe mode is on, shorten support disabled, md5data/sha1data for ogg vorbis disabled, ogg vorbos/flac tag writing disabled.');
		}

		// http://php.net/manual/en/mbstring.overload.php
		if (($mbstring_func_overload = (int)ini_get('mbstring.func_overload')) && ($mbstring_func_overload & 0x02)) {
			$this->startup_error .= 'WARNING: php.ini contains "mbstring.func_overload = ' . ini_get('mbstring.func_overload') . '", Getid3 cannot run with this setting (bitmask 2 (string functions) cannot be set). Recommended to disable entirely.'."\n";
		}

		if (!empty($this->startup_error)) {
			echo $this->startup_error;
			throw new Exception($this->startup_error);
		}
	}

	/**
	 * @return string
	 */
	public function version(): string
	{
		return self::VERSION;
	}

	/**
	 * @param string $filename
	 * @param int $filesize
	 * @param string $original_filename
	 * @param resource $fp
	 * @return array
	 */
	public function analyze(string $filename, int $filesize = null, string $original_filename = '', $fp = null): array
	{
		try {
			if (!$this->openfile($filename, $filesize, $fp)) {
				return $this->info;
			}

			// Handle tags
			try {
				$tag = new Tag($this);
				$tag->Analyze();
			}
			catch (Exception $e) {
				throw $e;
			}

			if (isset($this->info['id3v2']['tag_offset_start'])) {
				$this->info['avdataoffset'] = max($this->info['avdataoffset'], $this->info['id3v2']['tag_offset_end']);
			}
			if (isset($this->info['id3v1']['tag_offset_start'])) {
				$this->info['avdataend'] = min($this->info['avdataend'], $this->info['id3v1']['tag_offset_start']);
			}

			// Read 32 kb file data
			fseek($this->fp, $this->info['avdataoffset']);
			$formattest = fread($this->fp, 32774);

			// Determine format
			$determined_format = $this->GetFileFormat($formattest, ($original_filename ? $original_filename : $filename));

			// Unable to determine file format
			if (!$determined_format) {
				fclose($this->fp);
				return $this->error('unable to determine file format');
			}

			// Check for illegal ID3 tags
			if (isset($determined_format['fail_id3']) && (in_array('id3v1', $this->info['tags']) || in_array('id3v2', $this->info['tags']))) {
				if ($determined_format['fail_id3'] === 'ERROR') {
					fclose($this->fp);
					return $this->error('ID3 tags not allowed on this file type.');
				} elseif ($determined_format['fail_id3'] === 'WARNING') {
					$this->warning('ID3 tags not allowed on this file type.');
				}
			}

			// Check for illegal APE tags
			if (isset($determined_format['fail_ape']) && in_array('ape', $this->info['tags'])) {
				if ($determined_format['fail_ape'] === 'ERROR') {
					fclose($this->fp);
					return $this->error('APE tags not allowed on this file type.');
				} elseif ($determined_format['fail_ape'] === 'WARNING') {
					$this->warning('APE tags not allowed on this file type.');
				}
			}

			// Set mime type
			$this->info['mime_type'] = $determined_format['mime_type'];

			// Module requires mb_convert_encoding/iconv support
			// Check encoding/iconv support
			if (!empty($determined_format['iconv_req']) && !function_exists('mb_convert_encoding') && !function_exists('iconv') && !in_array($this->encoding, ['ISO-8859-1', 'UTF-8', 'UTF-16LE', 'UTF-16BE', 'UTF-16'])) {
				$errormessage = 'mb_convert_encoding() or iconv() support is required for this module for encodings other than ISO-8859-1, UTF-8, UTF-16LE, UTF16-BE, UTF-16. PHP is not compiled with mb_convert_encoding() or iconv() support. Please recompile with the --enable-mbstring / --with-iconv switch';
				return $this->error($errormessage);
			}

			// Instantiate module class
			$class = new File($this);
			$class->Analyze();
			unset($class);

			// Close file
			fclose($this->fp);

			// Process all tags - copy to 'tags' and convert charsets
			$this->HandleAllTags();

			// Perform more calculations
			$this->ChannelsBitratePlaytimeCalculations();
			$this->CalculateCompressionRatioVideo();
			$this->CalculateCompressionRatioAudio();
			$this->CalculateReplayGain();
			$this->ProcessAudioStreams();

			// Remove undesired keys
			$this->CleanUp();

		} catch (Exception $e) {
			$this->error('Caught exception: '.$e->getMessage());
		}

		// Return info array
		return $this->info;
	}

	/**
	 * @param string $message
	 * @return array
	 */
	public function error(string $message): array
	{
		$this->CleanUp();
		if (!isset($this->info['error'])) {
			$this->info['error'] = [];
		}
		$this->info['error'][] = $message;
		return $this->info;
	}

	/**
	 * @param string $message
	 * @return void
	 */
	public function warning(string $message)
	{
		$this->info['warning'][] = $message;
	}

	/**
	 * @param string $filename
	 * @param int $filesize
	 * @param resource $fp
	 * @return bool
	 * @throws Exception
	 */
	private function openfile(string $filename, int $filesize = null, $fp = null): bool
	{
		try {
			if (!empty($this->startup_error)) {
				throw new Exception($this->startup_error);
			}
			if (!empty($this->startup_warning)) {
				foreach (explode("\n", $this->startup_warning) as $startup_warning) {
					$this->warning($startup_warning);
				}
			}

			// Init result array and set parameters
			$this->filename = $filename;
			$this->info = [];
			$this->info['GETID3_VERSION']   = $this->version();
			$this->info['php_memory_limit'] = (($this->memory_limit > 0) ? $this->memory_limit : false);

			// Remote files not supported
			if (preg_match('#^(ht|f)tp://#', $filename)) {
				throw new Exception('Remote files are not supported - please copy the file locally first');
			}

			$filename = str_replace('/', DIRECTORY_SEPARATOR, $filename);

			// Open local file
			if (($fp != null) && ((get_resource_type($fp) == 'file') || (get_resource_type($fp) == 'stream'))) {
				$this->fp = $fp;
			} elseif ((is_readable($filename) || file_exists($filename)) && is_file($filename) && ($this->fp = fopen($filename, 'rb'))) {
				// Great
			} else {
				$errormessagelist = [];
				if (!is_readable($filename)) {
					$errormessagelist[] = '!is_readable';
				}
				if (!is_file($filename)) {
					$errormessagelist[] = '!is_file';
				}
				if (!file_exists($filename)) {
					$errormessagelist[] = '!file_exists';
				}
				if (empty($errormessagelist)) {
					$errormessagelist[] = 'fopen failed';
				}
				throw new Exception('Could not open "' . $filename . '" (' . implode('; ', $errormessagelist) . ')');
			}

			$this->info['filesize'] = (!is_null($filesize) ? $filesize : filesize($filename));
			// Set redundant parameters - might be needed in some include file
			// filenames / filepaths in Getid3 are always expressed with forward slashes (unix-style) for both Windows and other to try and minimize confusion
			$filename = str_replace('\\', '/', $filename);
			$this->info['filepath']     = str_replace('\\', '/', realpath(dirname($filename)));
			$this->info['filename']     = Library::mb_basename($filename);
			$this->info['filenamepath'] = $this->info['filepath'].'/'.$this->info['filename'];

			// Set more parameters
			$this->info['avdataoffset']        = 0;
			$this->info['avdataend']           = $this->info['filesize'];
			$this->info['fileformat']          = '';              // filled in later
			$this->info['audio']['dataformat'] = '';              // filled in later, unset if not used
			$this->info['video']['dataformat'] = '';              // filled in later, unset if not used
			$this->info['tags']                = [];           	  // filled in later, unset if not used
			$this->info['error']               = [];           	  // filled in later, unset if not used
			$this->info['warning']             = [];           	  // filled in later, unset if not used
			$this->info['comments']            = [];           	  // filled in later, unset if not used
			$this->info['encoding']            = $this->encoding; // required by id3v2 and iso modules - can be unset at the end if desired

			// PHP (32-bit all, and 64-bit Windows) doesn't support integers larger than 2^31 (~2GB)
			// filesize() simply returns (filesize % (pow(2, 32)), no matter the actual filesize
			// ftell() returns 0 if seeking to the end is beyond the range of unsigned integer
			$fseek = fseek($this->fp, 0, SEEK_END);
			if (($fseek < 0) || (($this->info['filesize'] != 0) && (ftell($this->fp) == 0)) ||
				($this->info['filesize'] < 0) ||
				(ftell($this->fp) < 0)) {
					$real_filesize = Library::getFileSizeSyscall($this->info['filenamepath']);

					if ($real_filesize === false) {
						unset($this->info['filesize']);
						fclose($this->fp);
						throw new Exception('Unable to determine actual filesize. File is most likely larger than ' . round(PHP_INT_MAX / 1073741824) . 'GB and is not supported by PHP.');
					} elseif (Library::intValueSupported($real_filesize)) {
						unset($this->info['filesize']);
						fclose($this->fp);
						throw new Exception('PHP seems to think the file is larger than ' . round(PHP_INT_MAX / 1073741824) . 'GB, but filesystem reports it as ' . number_format($real_filesize / 1073741824, 3) . 'GB, please report to info@getid3.org');
					}
					$this->info['filesize'] = $real_filesize;
					$this->warning('File is larger than ' . round(PHP_INT_MAX / 1073741824) . 'GB (filesystem reports it as ' . number_format($real_filesize / 1073741824, 3) . 'GB) and is not properly supported by PHP.');
			}

			return true;

		} catch (Exception $e) {
			$this->error($e->getMessage());
		}
		return false;
	}

	/**
	 * @return bool
	 */
	private function CleanUp(): bool
	{
		// Remove possible empty keys
		$AVpossibleEmptyKeys = ['dataformat', 'bits_per_sample', 'encoder_options', 'streams', 'bitrate'];
		foreach ($AVpossibleEmptyKeys as $dummy => $key) {
			if (empty($this->info['audio'][$key])) {
				unset($this->info['audio'][$key]);
			}
			if (empty($this->info['video'][$key])) {
				unset($this->info['video'][$key]);
			}
		}

		// Remove empty root keys
		if (!empty($this->info)) {
			foreach ($this->info as $key => $value) {
				if (empty($this->info[$key]) && ($this->info[$key] !== 0) && ($this->info[$key] !== '0')) {
					unset($this->info[$key]);
				}
			}
		}

		// Remove meaningless entries from unknown-format files
		if (empty($this->info['fileformat'])) {
			unset($this->info['avdataoffset'], $this->info['avdataend']);
		}

		// Remove possible duplicated identical entries
		if (!empty($this->info['error'])) {
			$this->info['error'] = array_values(array_unique($this->info['error']));
		}
		if (!empty($this->info['warning'])) {
			$this->info['warning'] = array_values(array_unique($this->info['warning']));
		}

		// Remove "global variable" type keys
		unset($this->info['php_memory_limit']);

		return true;
	}

	/**
	 * @return array
	 */
	private function GetFileFormatArray(): array
	{
		return [
			// Audio formats
			'ac3' => [
				'pattern'   => '^\\x0B\\x77',
				'group'     => 'audio',
				'module'    => 'ac3',
				'mime_type' => 'audio/ac3',
			],
			'adif' => [
				'pattern'   => '^ADIF',
				'group'     => 'audio',
				'module'    => 'aac',
				'mime_type' => 'audio/aac',
				'fail_ape'  => 'WARNING',
			],
			'adts' => [
				'pattern'   => '^\\xFF[\\xF0-\\xF1\\xF8-\\xF9]',
				'group'     => 'audio',
				'module'    => 'aac',
				'mime_type' => 'audio/aac',
				'fail_ape'  => 'WARNING',
			],
			'au' => [
				'pattern'   => '^\\.snd',
				'group'     => 'audio',
				'module'    => 'au',
				'mime_type' => 'audio/basic',
			],
			'amr' => [
				'pattern'   => '^\\x23\\x21AMR\\x0A', // #!AMR[0A]
				'group'     => 'audio',
				'module'    => 'amr',
				'mime_type' => 'audio/amr',
			],
			'avr' => [
				'pattern'   => '^2BIT',
				'group'     => 'audio',
				'module'    => 'avr',
				'mime_type' => 'application/octet-stream',
			],
			'bonk' => [
				'pattern'   => '^\\x00(BONK|INFO|META| ID3)',
				'group'     => 'audio',
				'module'    => 'bonk',
				'mime_type' => 'audio/xmms-bonk',
			],
			'dsf' => [
				'pattern'   => '^DSD ',  // including trailing space: 44 53 44 20
				'group'     => 'audio',
				'module'    => 'dsf',
				'mime_type' => 'audio/dsd',
			],
			'dss' => [
				'pattern'   => '^[\\x02-\\x08]ds[s2]',
				'group'     => 'audio',
				'module'    => 'dss',
				'mime_type' => 'application/octet-stream',
			],
			'dsdiff' => [
				'pattern'   => '^FRM8',
				'group'     => 'audio',
				'module'    => 'dsdiff',
				'mime_type' => 'audio/dsd',
			],
			'dts' => [
				'pattern'   => '^\\x7F\\xFE\\x80\\x01',
				'group'     => 'audio',
				'module'    => 'dts',
				'mime_type' => 'audio/dts',
			],
			'flac' => [
				'pattern'   => '^fLaC',
				'group'     => 'audio',
				'module'    => 'flac',
				'mime_type' => 'audio/flac',
			],
			'la' => [
				'pattern'   => '^LA0[2-4]',
				'group'     => 'audio',
				'module'    => 'la',
				'mime_type' => 'application/octet-stream',
			],
			'lpac' => [
				'pattern'   => '^LPAC',
				'group'     => 'audio',
				'module'    => 'lpac',
				'mime_type' => 'application/octet-stream',
			],
			'midi' => [
				'pattern'   => '^MThd',
				'group'     => 'audio',
				'module'    => 'midi',
				'mime_type' => 'audio/midi',
			],
			'mac' => [
				'pattern'   => '^MAC ',
				'group'     => 'audio',
				'module'    => 'monkey',
				'mime_type' => 'audio/x-monkeys-audio',
			],
			'it' => [
				'pattern'   => '^IMPM',
				'group'     => 'audio',
				'module'    => 'mod',
				'mime_type' => 'audio/it',
			],
			'xm' => [
				'pattern'   => '^Extended Module',
				'group'     => 'audio',
				'module'    => 'mod',
				'mime_type' => 'audio/xm',
			],
			's3m' => [
				'pattern'   => '^.{44}SCRM',
				'group'     => 'audio',
				'module'    => 'mod',
				'mime_type' => 'audio/s3m',
			],
			'mpc' => [
				'pattern'   => '^(MPCK|MP\\+|[\\x00\\x01\\x10\\x11\\x40\\x41\\x50\\x51\\x80\\x81\\x90\\x91\\xC0\\xC1\\xD0\\xD1][\\x20-\\x37][\\x00\\x20\\x40\\x60\\x80\\xA0\\xC0\\xE0])',
				'group'     => 'audio',
				'module'    => 'mpc',
				'mime_type' => 'audio/x-musepack',
			],
			'mp3' => [
				'pattern'   => '^\\xFF[\\xE2-\\xE7\\xF2-\\xF7\\xFA-\\xFF][\\x00-\\x0B\\x10-\\x1B\\x20-\\x2B\\x30-\\x3B\\x40-\\x4B\\x50-\\x5B\\x60-\\x6B\\x70-\\x7B\\x80-\\x8B\\x90-\\x9B\\xA0-\\xAB\\xB0-\\xBB\\xC0-\\xCB\\xD0-\\xDB\\xE0-\\xEB\\xF0-\\xFB]',
				'group'     => 'audio',
				'module'    => 'mp3',
				'mime_type' => 'audio/mpeg',
			],
			'ofr' => [
				'pattern'   => '^(\\*RIFF|OFR)',
				'group'     => 'audio',
				'module'    => 'optimfrog',
				'mime_type' => 'application/octet-stream',
			],
			'rkau' => [
				'pattern'   => '^RKA',
				'group'     => 'audio',
				'module'    => 'rkau',
				'mime_type' => 'application/octet-stream',
			],
			'shn' => [
				'pattern'   => '^ajkg',
				'group'     => 'audio',
				'module'    => 'shorten',
				'mime_type' => 'audio/xmms-shn',
				'fail_id3'  => 'ERROR',
				'fail_ape'  => 'ERROR',
			],
			'tak' => [
				'pattern'   => '^tBaK',
				'group'     => 'audio',
				'module'    => 'tak',
				'mime_type' => 'application/octet-stream',
			],
			'tta' => [
				'pattern'   => '^TTA',  // could also be '^TTA(\\x01|\\x02|\\x03|2|1)'
				'group'     => 'audio',
				'module'    => 'tta',
				'mime_type' => 'application/octet-stream',
			],
			'voc' => [
				'pattern'   => '^Creative Voice File',
				'group'     => 'audio',
				'module'    => 'voc',
				'mime_type' => 'audio/voc',
			],
			'vqf' => [
				'pattern'   => '^TWIN',
				'group'     => 'audio',
				'module'    => 'vqf',
				'mime_type' => 'application/octet-stream',
			],
			'wv' => [
				'pattern'   => '^wvpk',
				'group'     => 'audio',
				'module'    => 'wavpack',
				'mime_type' => 'application/octet-stream',
			],
			// Audio-Video formats
			'asf' => [
				'pattern'   => '^\\x30\\x26\\xB2\\x75\\x8E\\x66\\xCF\\x11\\xA6\\xD9\\x00\\xAA\\x00\\x62\\xCE\\x6C',
				'group'     => 'audio-video',
				'module'    => 'asf',
				'mime_type' => 'video/x-ms-asf',
				'iconv_req' => false,
			],
			'bink' => [
				'pattern'   => '^(BIK|SMK)',
				'group'     => 'audio-video',
				'module'    => 'bink',
				'mime_type' => 'application/octet-stream',
			],
			'flv' => [
				'pattern'   => '^FLV[\\x01]',
				'group'     => 'audio-video',
				'module'    => 'flv',
				'mime_type' => 'video/x-flv',
			],
			'ivf' => [
				'pattern'   => '^DKIF',
				'group'     => 'audio-video',
				'module'    => 'ivf',
				'mime_type' => 'video/x-ivf',
			],
			'matroska' => [
				'pattern'   => '^\\x1A\\x45\\xDF\\xA3',
				'group'     => 'audio-video',
				'module'    => 'matroska',
				'mime_type' => 'video/x-matroska', // may also be audio/x-matroska
			],
			'mpeg' => [
				'pattern'   => '^\\x00\\x00\\x01[\\xB3\\xBA]',
				'group'     => 'audio-video',
				'module'    => 'mpeg',
				'mime_type' => 'video/mpeg',
			],
			'nsv' => [
				'pattern'   => '^NSV[sf]',
				'group'     => 'audio-video',
				'module'    => 'nsv',
				'mime_type' => 'application/octet-stream',
			],
			'ogg' => [
				'pattern'   => '^OggS',
				'group'     => 'audio',
				'module'    => 'ogg',
				'mime_type' => 'application/ogg',
				'fail_id3'  => 'WARNING',
				'fail_ape'  => 'WARNING',
			],
			'quicktime' => [
				'pattern'   => '^.{4}(cmov|free|ftyp|mdat|moov|pnot|skip|wide)',
				'group'     => 'audio-video',
				'module'    => 'quicktime',
				'mime_type' => 'video/quicktime',
			],
			'riff' => [
				'pattern'   => '^(RIFF|SDSS|FORM)',
				'group'     => 'audio-video',
				'module'    => 'riff',
				'mime_type' => 'audio/wav',
				'fail_ape'  => 'WARNING',
			],
			'real' => [
				'pattern'   => '^\\.(RMF|ra)',
				'group'     => 'audio-video',
				'module'    => 'real',
				'mime_type' => 'audio/x-realaudio',
			],
			'swf' => [
				'pattern'   => '^(F|C)WS',
				'group'     => 'audio-video',
				'module'    => 'swf',
				'mime_type' => 'application/x-shockwave-flash',
			],
			'ts' => [
				'pattern'   => '^(\\x47.{187}){10,}', // packets are 188 bytes long and start with 0x47 "G".  Check for at least 10 packets matching this pattern
				'group'     => 'audio-video',
				'module'    => 'ts',
				'mime_type' => 'video/MP2T',
			],
			'wtv' => [
				'pattern'   => '^\\xB7\\xD8\\x00\\x20\\x37\\x49\\xDA\\x11\\xA6\\x4E\\x00\\x07\\xE9\\x5E\\xAD\\x8D',
				'group'     => 'audio-video',
				'module'    => 'wtv',
				'mime_type' => 'video/x-ms-wtv',
			],
			// Still-Image formats
			'bmp' => [
				'pattern'   => '^BM',
				'group'     => 'graphic',
				'module'    => 'bmp',
				'mime_type' => 'image/bmp',
				'fail_id3'  => 'ERROR',
				'fail_ape'  => 'ERROR',
			],
			'gif' => [
				'pattern'   => '^GIF',
				'group'     => 'graphic',
				'module'    => 'gif',
				'mime_type' => 'image/gif',
				'fail_id3'  => 'ERROR',
				'fail_ape'  => 'ERROR',
			],
			'jpg' => [
				'pattern'   => '^\\xFF\\xD8\\xFF',
				'group'     => 'graphic',
				'module'    => 'jpg',
				'mime_type' => 'image/jpeg',
				'fail_id3'  => 'ERROR',
				'fail_ape'  => 'ERROR',
			],
			'pcd' => [
				'pattern'   => '^.{2048}PCD_IPI\\x00',
				'group'     => 'graphic',
				'module'    => 'pcd',
				'mime_type' => 'image/x-photo-cd',
				'fail_id3'  => 'ERROR',
				'fail_ape'  => 'ERROR',
			],
			'png' => [
				'pattern'   => '^\\x89\\x50\\x4E\\x47\\x0D\\x0A\\x1A\\x0A',
				'group'     => 'graphic',
				'module'    => 'png',
				'mime_type' => 'image/png',
				'fail_id3'  => 'ERROR',
				'fail_ape'  => 'ERROR',
			],
			'svg' => [
				'pattern'   => '(<!DOCTYPE svg PUBLIC |xmlns="http://www\\.w3\\.org/2000/svg")',
				'group'     => 'graphic',
				'module'    => 'svg',
				'mime_type' => 'image/svg+xml',
				'fail_id3'  => 'ERROR',
				'fail_ape'  => 'ERROR',
			],
			'tiff' => [
				'pattern'   => '^(II\\x2A\\x00|MM\\x00\\x2A)',
				'group'     => 'graphic',
				'module'    => 'tiff',
				'mime_type' => 'image/tiff',
				'fail_id3'  => 'ERROR',
				'fail_ape'  => 'ERROR',
			],
			'efax' => [
				'pattern'   => '^\\xDC\\xFE',
				'group'     => 'graphic',
				'module'    => 'efax',
				'mime_type' => 'image/efax',
				'fail_id3'  => 'ERROR',
				'fail_ape'  => 'ERROR',
			],
			// Data formats
			'iso' => [
				'pattern'   => '^.{32769}CD001',
				'group'     => 'misc',
				'module'    => 'iso',
				'mime_type' => 'application/octet-stream',
				'fail_id3'  => 'ERROR',
				'fail_ape'  => 'ERROR',
				'iconv_req' => false,
			],
			'hpk' => [
				'pattern'   => '^BPUL',
				'group'     => 'archive',
				'module'    => 'hpk',
				'mime_type' => 'application/octet-stream',
				'fail_id3'  => 'ERROR',
				'fail_ape'  => 'ERROR',
			],
			'rar' => [
				'pattern'   => '^Rar\\!',
				'group'     => 'archive',
				'module'    => 'rar',
				'mime_type' => 'application/vnd.rar',
				'fail_id3'  => 'ERROR',
				'fail_ape'  => 'ERROR',
			],
			'szip' => [
				'pattern'   => '^SZ\\x0A\\x04',
				'group'     => 'archive',
				'module'    => 'szip',
				'mime_type' => 'application/octet-stream',
				'fail_id3'  => 'ERROR',
				'fail_ape'  => 'ERROR',
			],
			'tar' => [
				'pattern'   => '^.{100}[0-9\\x20]{7}\\x00[0-9\\x20]{7}\\x00[0-9\\x20]{7}\\x00[0-9\\x20\\x00]{12}[0-9\\x20\\x00]{12}',
				'group'     => 'archive',
				'module'    => 'tar',
				'mime_type' => 'application/x-tar',
				'fail_id3'  => 'ERROR',
				'fail_ape'  => 'ERROR',
			],
			'gz' => [
				'pattern'   => '^\\x1F\\x8B\\x08',
				'group'     => 'archive',
				'module'    => 'gzip',
				'mime_type' => 'application/gzip',
				'fail_id3'  => 'ERROR',
				'fail_ape'  => 'ERROR',
			],
			'zip' => [
				'pattern'   => '^PK\\x03\\x04',
				'group'     => 'archive',
				'module'    => 'zip',
				'mime_type' => 'application/zip',
				'fail_id3'  => 'ERROR',
				'fail_ape'  => 'ERROR',
			],
			'xz' => [
				'pattern'   => '^\\xFD7zXZ\\x00',
				'group'     => 'archive',
				'module'    => 'xz',
				'mime_type' => 'application/x-xz',
				'fail_id3'  => 'ERROR',
				'fail_ape'  => 'ERROR',
			],
			// Misc other formats
			'par2' => [
				'pattern'   => '^PAR2\\x00PKT',
				'group'     => 'misc',
				'module'    => 'par2',
				'mime_type' => 'application/octet-stream',
				'fail_id3'  => 'ERROR',
				'fail_ape'  => 'ERROR',
			],
			'pdf' => [
				'pattern'   => '^\\x25PDF',
				'group'     => 'misc',
				'module'    => 'pdf',
				'mime_type' => 'application/pdf',
				'fail_id3'  => 'ERROR',
				'fail_ape'  => 'ERROR',
			],
			'msoffice' => [
				'pattern'   => '^\\xD0\\xCF\\x11\\xE0\\xA1\\xB1\\x1A\\xE1', // D0CF11E == DOCFILE == Microsoft Office Document
				'group'     => 'misc',
				'module'    => 'msoffice',
				'mime_type' => 'application/octet-stream',
				'fail_id3'  => 'ERROR',
				'fail_ape'  => 'ERROR',
			],
			'torrent' => [
				'pattern'   => '^(d8\\:announce|d7\\:comment)',
				'group'     => 'misc',
				'module'    => 'torrent',
				'mime_type' => 'application/x-bittorrent',
				'fail_id3'  => 'ERROR',
				'fail_ape'  => 'ERROR',
			],
			'cue' => [ // CUEsheet (index to single-file disc images)
				'pattern'   => '', // empty pattern means cannot be automatically detected, will fall through all other formats and match based on filename and very basic file contents
				'group'     => 'misc',
				'module'    => 'cue',
				'mime_type' => 'application/octet-stream',
			]
		];
	}

	/**
	 * @param string $filedata
	 * @param string $filename
	 * @return mixed|false
	 */
	private function GetFileFormat(string &$filedata, string $filename = '')
	{
		// This function will determine the format of a file based on usually
		// the first 2-4 bytes of the file (8 bytes for PNG, 16 bytes for JPG,
		// and in the case of ISO CD image, 6 bytes offset 32kb from the start
		// of the file).

		// Identify file format - loop through $format_info and detect with reg expr
		foreach ($this->GetFileFormatArray() as $format_name => $info) {
			// The /s switch on preg_match() forces preg_match() NOT to treat
			// newline (0x0A) characters as special chars but do a binary match
			if (!empty($info['pattern']) && preg_match('#' . $info['pattern'].'#s', $filedata)) {
				return $info;
			}
		}

		if (preg_match('#\\.mp[123a]$#i', $filename)) {
			// Too many mp3 encoders on the market put garbage in front of mpeg files
			// use assume format on these if format detection failed
			$GetFileFormatArray = $this->GetFileFormatArray();
			$info = $GetFileFormatArray['mp3'];
			return $info;
		} elseif (preg_match('#\\.cue$#i', $filename) && preg_match('#FILE "[^"]+" (BINARY|MOTOROLA|AIFF|WAVE|MP3)#', $filedata)) {
			// There's not really a useful consistent "magic" at the beginning of .cue files to identify them
			// so until I think of something better, just go by filename if all other format checks fail
			// and verify there's at least one instance of "TRACK xx AUDIO" in the file
			$GetFileFormatArray = $this->GetFileFormatArray();
			$info = $GetFileFormatArray['cue'];
			return $info;
		}

		return false;
	}

	/**
	 * @param array $array
	 * @param string $encoding
	 * @return void
	 */
	private function CharConvert(array &$array, string $encoding) 
	{
		if ($encoding == $this->encoding) {
			return;
		}

		foreach ($array as $key => $value) {
			if (is_array($value)) {
				$this->CharConvert($array[$key], $encoding);
			}
			elseif (is_string($value)) {
				$array[$key] = trim(Library::iconv_fallback($encoding, $this->encoding, $value));
			}
		}
	}

	/**
	 * @return bool
	 */
	private function HandleAllTags(): bool
	{
		static $tags;
		if (empty($tags)) {
			$tags = [
				'asf'       => ['asf', 			'UTF-16LE'],
				'midi'      => ['midi', 		'ISO-8859-1'],
				'nsv'       => ['nsv', 			'ISO-8859-1'],
				'ogg'       => ['vorbiscomment','UTF-8'],
				'png'       => ['png', 			'UTF-8'],
				'tiff'      => ['tiff', 		'ISO-8859-1'],
				'quicktime' => ['quicktime', 	'UTF-8'],
				'real'      => ['real', 		'ISO-8859-1'],
				'vqf'       => ['vqf', 			'ISO-8859-1'],
				'zip'       => ['zip', 			'ISO-8859-1'],
				'riff'      => ['riff', 		'ISO-8859-1'],
				'lyrics3'   => ['lyrics3', 		'ISO-8859-1'],
				'id3v1'     => ['id3v1', 		$this->encoding_id3v1],
				'id3v2'     => ['id3v2', 		'UTF-8'], // not according to the specs (every frame can have a different encoding), but Getid3() force-converts all encodings to UTF-8
				'ape'       => ['ape', 			'UTF-8'],
				'cue'       => ['cue', 			'ISO-8859-1'],
				'matroska'  => ['matroska', 	'UTF-8'],
				'flac'      => ['vorbiscomment','UTF-8'],
				'divxtag'   => ['divx', 		'ISO-8859-1'],
				'iptc'      => ['iptc', 		'ISO-8859-1'],
				'dsdiff'    => ['dsdiff', 		'ISO-8859-1']
			];
		}

		// Loop through comments array
		foreach ($tags as $comment_name => $tagname_encoding_array) {
			list($tag_name, $encoding) = $tagname_encoding_array;

			// Fill in default encoding type if not already present
			if (isset($this->info[$comment_name]) && !isset($this->info[$comment_name]['encoding'])) {
				$this->info[$comment_name]['encoding'] = $encoding;
			}

			// Copy comments if key name set
			if (!empty($this->info[$comment_name]['comments'])) {
				foreach ($this->info[$comment_name]['comments'] as $tag_key => $valuearray) {
					foreach ($valuearray as $key => $value) {
						if (is_string($value)) {
							// Do not trim nulls from $value!! Unicode characters will get mangled if trailing nulls are removed!
							$value = trim($value, " \r\n\t"); 
						}
						if ($value) {
							if (!is_numeric($key)) {
								$this->info['tags'][trim($tag_name)][trim($tag_key)][$key] = $value;
							} else {
								$this->info['tags'][trim($tag_name)][trim($tag_key)][]     = $value;
							}
						}
					}
					if ($tag_key == 'picture') {
						// pictures can take up a lot of space, and we don't need multiple copies of them; let 
						// there be a single copy in [comments][picture], and not elsewhere
						unset($this->info[$comment_name]['comments'][$tag_key]);
					}
				}

				if (!isset($this->info['tags'][$tag_name])) {
					// Comments are set but contain nothing but empty strings, so skip
					continue;
				}

				$this->CharConvert($this->info['tags'][$tag_name], $this->info[$comment_name]['encoding']); // only copy gets converted!

				foreach ($this->info['tags'][$tag_name] as $tag_key => $valuearray) {
					if ($tag_key == 'picture') {
						// Do not to try to convert binary picture data to HTML
						// https://github.com/JamesHeinrich/getid3/issues/178
						continue;
					}
					$this->info['tags_html'][$tag_name][$tag_key] = Library::recursiveMultiByteCharString2HTML($valuearray, $this->info[$comment_name]['encoding']);
				}
			}
		}

		// Pictures can take up a lot of space, and we don't need multiple copies of them; let there be a single copy in [comments][picture], and not elsewhere
		if (!empty($this->info['tags'])) {
			$unset_keys = ['tags', 'tags_html'];
			foreach ($this->info['tags'] as $tagtype => $tagarray) {
				foreach ($tagarray as $tagname => $tagdata) {
					if ($tagname == 'picture') {
						foreach ($tagdata as $key => $tagarray) {
							$this->info['comments']['picture'][] = $tagarray;
							if (isset($tagarray['data']) && isset($tagarray['image_mime'])) {
								if (isset($this->info['tags'][$tagtype][$tagname][$key])) {
									unset($this->info['tags'][$tagtype][$tagname][$key]);
								}
								if (isset($this->info['tags_html'][$tagtype][$tagname][$key])) {
									unset($this->info['tags_html'][$tagtype][$tagname][$key]);
								}
							}
						}
					}
				}
				foreach ($unset_keys as $unset_key) {
					// Remove possible empty keys from (e.g. [tags][id3v2][picture])
					if (empty($this->info[$unset_key][$tagtype]['picture'])) {
						unset($this->info[$unset_key][$tagtype]['picture']);
					}
					if (empty($this->info[$unset_key][$tagtype])) {
						unset($this->info[$unset_key][$tagtype]);
					}
					if (empty($this->info[$unset_key])) {
						unset($this->info[$unset_key]);
					}
				}
				// Remove duplicate copy of picture data from (e.g. [id3v2][comments][picture])
				if (isset($this->info[$tagtype]['comments']['picture'])) {
					unset($this->info[$tagtype]['comments']['picture']);
				}
				if (empty($this->info[$tagtype]['comments'])) {
					unset($this->info[$tagtype]['comments']);
				}
				if (empty($this->info[$tagtype])) {
					unset($this->info[$tagtype]);
				}
			}
		}
		return true;
	}

	/**
	 * @return void
	 */
	private function ChannelsBitratePlaytimeCalculations() 
	{
		// Set channelmode on audio
		if (!empty($this->info['audio']['channelmode']) || !isset($this->info['audio']['channels'])) {
			// Ignore
		} elseif ($this->info['audio']['channels'] == 1) {
			$this->info['audio']['channelmode'] = 'mono';
		} elseif ($this->info['audio']['channels'] == 2) {
			$this->info['audio']['channelmode'] = 'stereo';
		}

		// Calculate combined bitrate - audio + video
		$CombinedBitrate  = 0;
		$CombinedBitrate += (isset($this->info['audio']['bitrate']) ? $this->info['audio']['bitrate'] : 0);
		$CombinedBitrate += (isset($this->info['video']['bitrate']) ? $this->info['video']['bitrate'] : 0);
		if (($CombinedBitrate > 0) && empty($this->info['bitrate'])) {
			$this->info['bitrate'] = $CombinedBitrate;
		}

		// Video bitrate undetermined, but calculable
		if (isset($this->info['video']['dataformat']) && $this->info['video']['dataformat'] && (!isset($this->info['video']['bitrate']) || ($this->info['video']['bitrate'] == 0))) {
			// If video bitrate not set
			if (isset($this->info['audio']['bitrate']) && ($this->info['audio']['bitrate'] > 0) && ($this->info['audio']['bitrate'] == $this->info['bitrate'])) {
				// AND if audio bitrate is set to same as overall bitrate
				if (isset($this->info['playtime_seconds']) && ($this->info['playtime_seconds'] > 0)) {
					// AND if playtime is set
					if (isset($this->info['avdataend']) && isset($this->info['avdataoffset'])) {
						// AND if AV data offset start/end is known
						// THEN we can calculate the video bitrate
						$this->info['bitrate'] = round((($this->info['avdataend'] - $this->info['avdataoffset']) * 8) / $this->info['playtime_seconds']);
						$this->info['video']['bitrate'] = $this->info['bitrate'] - $this->info['audio']['bitrate'];
					}
				}
			}
		}

		if ((!isset($this->info['playtime_seconds']) || ($this->info['playtime_seconds'] <= 0)) && !empty($this->info['bitrate'])) {
			$this->info['playtime_seconds'] = (($this->info['avdataend'] - $this->info['avdataoffset']) * 8) / $this->info['bitrate'];
		}

		if (!isset($this->info['bitrate']) && !empty($this->info['playtime_seconds'])) {
			$this->info['bitrate'] = (($this->info['avdataend'] - $this->info['avdataoffset']) * 8) / $this->info['playtime_seconds'];
		}
		if (isset($this->info['bitrate']) && empty($this->info['audio']['bitrate']) && empty($this->info['video']['bitrate'])) {
			if (isset($this->info['audio']['dataformat']) && empty($this->info['video']['resolution_x'])) {
				// Audio only
				$this->info['audio']['bitrate'] = $this->info['bitrate'];
			} elseif (isset($this->info['video']['resolution_x']) && empty($this->info['audio']['dataformat'])) {
				// Video only
				$this->info['video']['bitrate'] = $this->info['bitrate'];
			}
		}

		// Set playtime string
		if (!empty($this->info['playtime_seconds']) && empty($this->info['playtime_string'])) {
			$this->info['playtime_string'] = Library::PlaytimeString($this->info['playtime_seconds']);
		}
	}

	/**
	 * @return bool
	 */
	private function CalculateCompressionRatioVideo(): bool
	{
		if (empty($this->info['video'])) {
			return false;
		}
		if (empty($this->info['video']['resolution_x']) || empty($this->info['video']['resolution_y'])) {
			return false;
		}
		if (empty($this->info['video']['bits_per_sample'])) {
			return false;
		}

		switch ($this->info['video']['dataformat']) {
			case 'bmp':
			case 'gif':
			case 'jpeg':
			case 'jpg':
			case 'png':
			case 'tiff':
				$FrameRate = 1;
				$PlaytimeSeconds = 1;
				$BitrateCompressed = $this->info['filesize'] * 8;
				break;

			default:
				if (!empty($this->info['video']['frame_rate'])) {
					$FrameRate = $this->info['video']['frame_rate'];
				} else {
					return false;
				}
				if (!empty($this->info['playtime_seconds'])) {
					$PlaytimeSeconds = $this->info['playtime_seconds'];
				} else {
					return false;
				}
				if (!empty($this->info['video']['bitrate'])) {
					$BitrateCompressed = $this->info['video']['bitrate'];
				} else {
					return false;
				}
				break;
		}
		$BitrateUncompressed = $this->info['video']['resolution_x'] * $this->info['video']['resolution_y'] * $this->info['video']['bits_per_sample'] * $FrameRate;

		$this->info['video']['compression_ratio'] = $BitrateCompressed / $BitrateUncompressed;
		return true;
	}

	/**
	 * @return bool
	 */
	private function CalculateCompressionRatioAudio(): bool
	{
		if (empty($this->info['audio']['bitrate']) || empty($this->info['audio']['channels']) || empty($this->info['audio']['sample_rate']) || !is_numeric($this->info['audio']['sample_rate'])) {
			return false;
		}
		$this->info['audio']['compression_ratio'] = $this->info['audio']['bitrate'] / ($this->info['audio']['channels'] * $this->info['audio']['sample_rate'] * (!empty($this->info['audio']['bits_per_sample']) ? $this->info['audio']['bits_per_sample'] : 16));

		if (!empty($this->info['audio']['streams'])) {
			foreach ($this->info['audio']['streams'] as $streamnumber => $streamdata) {
				if (!empty($streamdata['bitrate']) && !empty($streamdata['channels']) && !empty($streamdata['sample_rate'])) {
					$this->info['audio']['streams'][$streamnumber]['compression_ratio'] = $streamdata['bitrate'] / ($streamdata['channels'] * $streamdata['sample_rate'] * (!empty($streamdata['bits_per_sample']) ? $streamdata['bits_per_sample'] : 16));
				}
			}
		}
		return true;
	}

	/**
	 * @return bool
	 */
	private function CalculateReplayGain(): bool
	{
		if (isset($this->info['replay_gain'])) {
			if (!isset($this->info['replay_gain']['reference_volume'])) {
				$this->info['replay_gain']['reference_volume'] = 89.0;
			}
			if (isset($this->info['replay_gain']['track']['adjustment'])) {
				$this->info['replay_gain']['track']['volume'] = $this->info['replay_gain']['reference_volume'] - $this->info['replay_gain']['track']['adjustment'];
			}
			if (isset($this->info['replay_gain']['album']['adjustment'])) {
				$this->info['replay_gain']['album']['volume'] = $this->info['replay_gain']['reference_volume'] - $this->info['replay_gain']['album']['adjustment'];
			}

			if (isset($this->info['replay_gain']['track']['peak'])) {
				$this->info['replay_gain']['track']['max_noclip_gain'] = 0 - Library::RGADamplitude2dB($this->info['replay_gain']['track']['peak']);
			}
			if (isset($this->info['replay_gain']['album']['peak'])) {
				$this->info['replay_gain']['album']['max_noclip_gain'] = 0 - Library::RGADamplitude2dB($this->info['replay_gain']['album']['peak']);
			}
		}
		return true;
	}

	/**
	 * @return bool
	 */
	private function ProcessAudioStreams(): bool
	{
		if (!empty($this->info['audio']['bitrate']) || !empty($this->info['audio']['channels']) || !empty($this->info['audio']['sample_rate'])) {
			if (!isset($this->info['audio']['streams'])) {
				foreach ($this->info['audio'] as $key => $value) {
					if ($key != 'streams') {
						$this->info['audio']['streams'][0][$key] = $value;
					}
				}
			}
		}
		return true;
	}
}

abstract class Handler
{
	/** @var Getid3 pointer*/
	protected $getid3;

	/** @var bool Analyzing filepointer or string */
	protected $data_string_flag = false;

	/** @var string String to analyze */
	protected $data_string = '';

	/**@var int Seek position in string */
	protected $data_string_position = 0;

	/** @var int */
	protected $data_string_length = 0;

	/** @var string */
	private $dependency_to;

	/**
	 * @param Getid3 $getid3
	 * @param string $call_module
	 * @return void
	 */
	public function __construct(Getid3 $getid3, string $call_module = null)
	{
		$this->getid3 = $getid3;

		if ($call_module) {
			$this->dependency_to = str_replace('getid3_', '', $call_module);
		}
	}

	/**
	 * Analyze from file pointer
	 * @return bool
	 */
	abstract public function Analyze(): bool;

	/**
	 * Analyze from string instead
	 * @param string $string
	 * @return void
	 */
	public function AnalyzeString(string $string)
	{
		$this->setStringMode($string);

		// Save info
		$saved_avdataoffset = $this->getid3->info['avdataoffset'];
		$saved_avdataend    = $this->getid3->info['avdataend'];

		// May be not set if called as dependency without openfile() call
		$saved_filesize     = (isset($this->getid3->info['filesize']) ? $this->getid3->info['filesize'] : null);

		// Reset some info
		$this->getid3->info['avdataoffset'] = 0;
		$this->getid3->info['avdataend']    = $this->getid3->info['filesize'] = $this->data_string_length;

		// Analyze
		$this->Analyze();

		// Restore some info
		$this->getid3->info['avdataoffset'] = $saved_avdataoffset;
		$this->getid3->info['avdataend']    = $saved_avdataend;
		$this->getid3->info['filesize']     = $saved_filesize;

		// Exit string mode
		$this->data_string_flag = false;
	}

	/**
	 * @param string $string
	 */
	public function setStringMode(string $string)
	{
		$this->data_string_flag   = true;
		$this->data_string        = $string;
		$this->data_string_length = strlen($string);
	}

	/**
	 * @param string $name
	 * @param int $offset
	 * @param int $length
	 * @param string $image_mime
	 * @return string|null
	 * @throws Exception
	 */
	public function saveAttachment(string $name, int $offset, int $length, string $image_mime = null)
	{
		try {
			$this->fseek($offset);
			$attachment = $this->fread($length); // get whole data in one pass, till it is anyway stored in memory
			if ($attachment === false || strlen($attachment) != $length) {
				throw new Exception('failed to read attachment data');
			}

		} catch (Exception $e) {

			// Close and remove dest file if created
			if (isset($fp_dest) && is_resource($fp_dest)) {
				fclose($fp_dest);
			}

			if (isset($dest) && file_exists($dest)) {
				unlink($dest);
			}

			// Do not set any is case of error
			$attachment = null;
			$this->warning('Failed to extract attachment ' . $name . ': ' . $e->getMessage());
		}

		// Seek to the end of attachment
		$this->fseek($offset + $length);

		return $attachment;
	}

	/**
	 * @return int|bool
	 */
	protected function ftell()
	{
		if ($this->data_string_flag) {
			return $this->data_string_position;
		}
		return ftell($this->getid3->fp);
	}

	/**
	 * @param int $bytes
	 * @return string|false
	 * @throws Exception
	 */
	protected function fread(int $bytes)
	{
		if ($this->data_string_flag) {
			$this->data_string_position += $bytes;
			return substr($this->data_string, $this->data_string_position - $bytes, $bytes);
		}
		$pos = $this->ftell() + $bytes;
		if (!Library::intValueSupported($pos)) {
			throw new Exception('cannot fread('.$bytes.' from '.$this->ftell().') because beyond PHP filesystem limit', 10);
		}

		$contents = '';
		do {
			// Enable a more-fuzzy match to prevent close misses generating errors like "PHP Fatal error: 
			// Allowed memory size of 33554432 bytes exhausted (tried to allocate 33554464 bytes)"
			if (($this->getid3->memory_limit > 0) && (($bytes / $this->getid3->memory_limit) > 0.99)) {
				throw new Exception('cannot fread(' . $bytes . ' from ' . $this->ftell() . ') that is more than available PHP memory (' . $this->getid3->memory_limit . ')', 10);
			}
			$part = fread($this->getid3->fp, $bytes);
			$partLength  = strlen($part);
			$bytes      -= $partLength;
			$contents   .= $part;
		} while (($bytes > 0) && ($partLength > 0));
		return $contents;
	}

	/**
	 * @param int $bytes
	 * @param int $whence
	 * @return int
	 * @throws Exception
	 */
	protected function fseek(int $bytes, int $whence = SEEK_SET): int
	{
		if ($this->data_string_flag) {
			switch ($whence) {
				case SEEK_SET:
					$this->data_string_position = $bytes;
					break;

				case SEEK_CUR:
					$this->data_string_position += $bytes;
					break;

				case SEEK_END:
					$this->data_string_position = $this->data_string_length + $bytes;
					break;
			}
			return 0;
		} else {
			$pos = $bytes;
			if ($whence == SEEK_CUR) {
				$pos = $this->ftell() + $bytes;
			} elseif ($whence == SEEK_END) {
				$pos = $this->getid3->info['filesize'] + $bytes;
			}
			if (!Library::intValueSupported($pos)) {
				throw new Exception('cannot fseek('.$pos.') because beyond PHP filesystem limit', 10);
			}
		}
		return fseek($this->getid3->fp, $bytes, $whence);
	}

	/**
	 * @return string|false
	 * @throws Exception
	 */
	protected function fgets()
	{
		// Must be able to handle CR/LF/CRLF but not read more than one line end
		$buffer   = ''; // final string we will return
		$prevchar = ''; // save previously-read character for end-of-line checking
		if ($this->data_string_flag) {
			while (true) {
				$thischar = substr($this->data_string, $this->data_string_position++, 1);
				if (($prevchar == "\r") && ($thischar != "\n")) {
					// read one byte too many, back up
					$this->data_string_position--;
					break;
				}
				$buffer .= $thischar;
				if ($thischar == "\n") {
					break;
				}
				if ($this->data_string_position >= $this->data_string_length) {
					// EOF
					break;
				}
				$prevchar = $thischar;
			}

		} else {

			// Ideally we would just use PHP's fgets() function, however...
			// it does not behave consistently with regards to mixed line endings, may be system-dependent
			// and breaks entirely when given a file with mixed \r vs \n vs \r\n line endings (e.g. some PDFs)
			while (true) {
				$thischar = fgetc($this->getid3->fp);
				if ($prevchar == "\r" && $thischar != "\n") {
					// Read one byte too many, back up
					fseek($this->getid3->fp, -1, SEEK_CUR);
					break;
				}
				$buffer .= $thischar;
				if ($thischar == "\n") {
					break;
				}
				if (feof($this->getid3->fp)) {
					break;
				}
				$prevchar = $thischar;
			}

		}
		return $buffer;
	}

	/**
	 * @return bool
	 */
	protected function feof(): bool
	{
		if ($this->data_string_flag) {
			return $this->data_string_position >= $this->data_string_length;
		}
		return feof($this->getid3->fp);
	}

	/**
	 * @param string $module
	 * @return bool
	 */
	final protected function isDependencyFor(string $module): bool
	{
		return $this->dependency_to == $module;
	}

	/**
	 * @param string $text
	 * @return bool
	 */
	protected function error(string $text): bool
	{
		$this->getid3->info['error'][] = $text;

		return false;
	}

	/**
	 * @param string $text
	 * @return bool | null
	 */
	protected function warning(string $text): ?bool
	{
		return $this->getid3->warning($text);
	}
}

class Exception extends \Exception
{
	public $message;
}
