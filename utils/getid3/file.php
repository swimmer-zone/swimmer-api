<?php

namespace Swimmer\Utils\Getid3;

/**
 * @author James Heinrich <info@getid3.org>
 * @see https://github.com/JamesHeinrich/getid3
 * @see https://www.getid3.org
 * @see http://getid3.sourceforge.net
 */
class File extends Handler
{
	/**
	 * Number of frames to scan to determine if MPEG-audio sequence is valid
	 * Lower this number to 5-20 for faster scanning
	 * Increase this number to 50+ for most accurate detection of valid VBR/CBR
	 * mpeg-audio streams
	 */
 	private const GETID3_MP3_VALID_CHECK_FRAMES = 35;

	/**
	 * @return bool
	 */
	public function Analyze(): bool
	{
		$info = &$this->getid3->info;

		$initialOffset = $info['avdataoffset'];

		if (isset($info['mpeg']['audio']['bitrate_mode'])) {
			$info['audio']['bitrate_mode'] = strtolower($info['mpeg']['audio']['bitrate_mode']);
		}

		if ((isset($info['id3v2']['headerlength']) && $info['avdataoffset'] > $info['id3v2']['headerlength']) 
			|| (!isset($info['id3v2']) && $info['avdataoffset'] > 0 && $info['avdataoffset'] != $initialOffset)) {

			$synchoffsetwarning = 'Unknown data before synch ';
			if (isset($info['id3v2']['headerlength'])) {
				$synchoffsetwarning .= '(ID3v2 header ends at ' . $info['id3v2']['headerlength'] . ', then ' . ($info['avdataoffset'] - $info['id3v2']['headerlength']) . ' bytes garbage, ';
			} elseif ($initialOffset > 0) {
				$synchoffsetwarning .= '(should be at ' . $initialOffset . ', ';
			} else {
				$synchoffsetwarning .= '(should be at beginning of file, ';
			}
			$synchoffsetwarning .= 'synch detected at ' . $info['avdataoffset'] . ')';
			if (isset($info['audio']['bitrate_mode']) && ($info['audio']['bitrate_mode'] == 'cbr')) {

				if (!empty($info['id3v2']['headerlength']) && ($info['avdataoffset'] - $info['id3v2']['headerlength']) == $info['mpeg']['audio']['framelength']) {

					$synchoffsetwarning .= '. This is a known problem with some versions of LAME (3.90-3.92) DLL in CBR mode.';
					$info['audio']['codec'] = 'LAME';
					$CurrentDataLAMEversionString = 'LAME3.';

				} elseif (empty($info['id3v2']['headerlength']) && ($info['avdataoffset'] == $info['mpeg']['audio']['framelength'])) {

					$synchoffsetwarning .= '. This is a known problem with some versions of LAME (3.90 - 3.92) DLL in CBR mode.';
					$info['audio']['codec'] = 'LAME';
					$CurrentDataLAMEversionString = 'LAME3.';
				}
			}
			$this->warning($synchoffsetwarning);

		}

		if (isset($info['mpeg']['audio']['LAME'])) {
			$info['audio']['codec'] = 'LAME';
			if (!empty($info['mpeg']['audio']['LAME']['long_version'])) {
				$info['audio']['encoder'] = rtrim($info['mpeg']['audio']['LAME']['long_version'], "\x00");
			} elseif (!empty($info['mpeg']['audio']['LAME']['short_version'])) {
				$info['audio']['encoder'] = rtrim($info['mpeg']['audio']['LAME']['short_version'], "\x00");
			}
		}

		$CurrentDataLAMEversionString = (!empty($CurrentDataLAMEversionString) ? $CurrentDataLAMEversionString : (isset($info['audio']['encoder']) ? $info['audio']['encoder'] : ''));

		// A version number of LAME that does not end with a number like "LAME3.92" or with a closing parenthesis 
		// like "LAME3.88 (alpha)" or a version of LAME with the LAMEtag-not-filled-in-DLL-mode bug (3.90-3.92)
		if (!empty($CurrentDataLAMEversionString) && substr($CurrentDataLAMEversionString, 0, 6) == 'LAME3.' && !preg_match('[0-9\)]', substr($CurrentDataLAMEversionString, -1))) {

			// Not sure what the actual last frame length will be, but will be less than or equal to 1441
			$PossiblyLongerLAMEversion_FrameLength = 1441;

			// Not sure what version of LAME this is - look in padding of last frame for longer version string
			$PossibleLAMEversionStringOffset = $info['avdataend'] - $PossiblyLongerLAMEversion_FrameLength;
			$this->fseek($PossibleLAMEversionStringOffset);
			$PossiblyLongerLAMEversion_Data = $this->fread($PossiblyLongerLAMEversion_FrameLength);
			switch (substr($CurrentDataLAMEversionString, -1)) {
				case 'a':
				case 'b':
					// "LAME3.94a" will have a longer version string of "LAME3.94 (alpha)" for example
					// need to trim off "a" to match longer string
					$CurrentDataLAMEversionString = substr($CurrentDataLAMEversionString, 0, -1);
					break;
			}
			if (($PossiblyLongerLAMEversion_String = strstr($PossiblyLongerLAMEversion_Data, $CurrentDataLAMEversionString)) !== false) {
				if (substr($PossiblyLongerLAMEversion_String, 0, strlen($CurrentDataLAMEversionString)) == $CurrentDataLAMEversionString) {
					$PossiblyLongerLAMEversion_NewString = substr($PossiblyLongerLAMEversion_String, 0, strspn($PossiblyLongerLAMEversion_String, 'LAME0123456789., (abcdefghijklmnopqrstuvwxyzJFSOND)')); //"LAME3.90.3"  "LAME3.87 (beta 1, Sep 27 2000)" "LAME3.88 (beta)"
					if (empty($info['audio']['encoder']) || (strlen($PossiblyLongerLAMEversion_NewString) > strlen($info['audio']['encoder']))) {
						$info['audio']['encoder'] = $PossiblyLongerLAMEversion_NewString;
					}
				}
			}
		}
		if (!empty($info['audio']['encoder'])) {
			$info['audio']['encoder'] = rtrim($info['audio']['encoder'], "\x00 ");
		}

		switch (isset($info['mpeg']['audio']['layer']) ? $info['mpeg']['audio']['layer'] : '') {
			case 1:
			case 2:
				$info['audio']['dataformat'] = 'mp'.$info['mpeg']['audio']['layer'];
				break;
		}
		if (isset($info['fileformat']) && $info['fileformat'] == 'mp3') {
			switch ($info['audio']['dataformat']) {
				case 'mp1':
				case 'mp2':
				case 'mp3':
					$info['fileformat'] = $info['audio']['dataformat'];
					break;

				default:
					$this->warning('Expecting [audio][dataformat] to be mp1/mp2/mp3 when fileformat == mp3, [audio][dataformat] actually "' . $info['audio']['dataformat'] . '"');
					break;
			}
		}

		if (empty($info['fileformat'])) {
			unset($info['fileformat'], $info['audio']['bitrate_mode'], $info['avdataoffset'], $info['avdataend']);
			return false;
		}

		$info['mime_type']         = 'audio/mpeg';
		$info['audio']['lossless'] = false;

		// Calculate playtime
		if (!isset($info['playtime_seconds']) && isset($info['audio']['bitrate']) && $info['audio']['bitrate'] > 0) {
			// https://github.com/JamesHeinrich/getid3/issues/161
			// VBR header frame contains ~0.026s of silent audio data, but is not actually part of the original encoding and should be ignored
			$xingVBRheaderFrameLength = ((isset($info['mpeg']['audio']['VBR_frames']) && isset($info['mpeg']['audio']['framelength'])) ? $info['mpeg']['audio']['framelength'] : 0);

			$info['playtime_seconds'] = ($info['avdataend'] - $info['avdataoffset'] - $xingVBRheaderFrameLength) * 8 / $info['audio']['bitrate'];
		}

		$info['audio']['encoder_options'] = $this->GuessEncoderOptions();

		return true;
	}

	/**
	 * @return string
	 */
	private function GuessEncoderOptions(): string
	{
		// Shortcuts
		$info = &$this->getid3->info;
		$thisfile_mpeg_audio = [];
		$thisfile_mpeg_audio_lame = [];
		if (!empty($info['mpeg']['audio'])) {
			$thisfile_mpeg_audio = &$info['mpeg']['audio'];
			if (!empty($thisfile_mpeg_audio['LAME'])) {
				$thisfile_mpeg_audio_lame = &$thisfile_mpeg_audio['LAME'];
			}
		}

		$encoder_options = '';
		static $NamedPresetBitrates = [16, 24, 40, 56, 112, 128, 160, 192, 256];

		if (isset($thisfile_mpeg_audio['VBR_method']) && $thisfile_mpeg_audio['VBR_method'] == 'Fraunhofer' && !empty($thisfile_mpeg_audio['VBR_quality'])) {

			$encoder_options = 'VBR q' . $thisfile_mpeg_audio['VBR_quality'];

		} elseif (!empty($thisfile_mpeg_audio_lame['preset_used']) && isset($thisfile_mpeg_audio_lame['preset_used_id']) && !in_array($thisfile_mpeg_audio_lame['preset_used_id'], $NamedPresetBitrates)) {

			$encoder_options = $thisfile_mpeg_audio_lame['preset_used'];

		} elseif (!empty($thisfile_mpeg_audio_lame['vbr_quality'])) {

			static $KnownEncoderValues = [];
			if (empty($KnownEncoderValues)) {

				$KnownEncoderValues[0xFF][58][1][1][3][2][20500] = '--alt-preset insane';        // 3.90,   3.90.1, 3.92
				$KnownEncoderValues[0xFF][58][1][1][3][2][20600] = '--alt-preset insane';        // 3.90.2, 3.90.3, 3.91
				$KnownEncoderValues[0xFF][57][1][1][3][4][20500] = '--alt-preset insane';        // 3.94,   3.95
				$KnownEncoderValues['**'][78][3][2][3][2][19500] = '--alt-preset extreme';       // 3.90,   3.90.1, 3.92
				$KnownEncoderValues['**'][78][3][2][3][2][19600] = '--alt-preset extreme';       // 3.90.2, 3.91
				$KnownEncoderValues['**'][78][3][1][3][2][19600] = '--alt-preset extreme';       // 3.90.3
				$KnownEncoderValues['**'][78][4][2][3][2][19500] = '--alt-preset fast extreme';  // 3.90,   3.90.1, 3.92
				$KnownEncoderValues['**'][78][4][2][3][2][19600] = '--alt-preset fast extreme';  // 3.90.2, 3.90.3, 3.91
				$KnownEncoderValues['**'][78][3][2][3][4][19000] = '--alt-preset standard';      // 3.90,   3.90.1, 3.90.2, 3.91, 3.92
				$KnownEncoderValues['**'][78][3][1][3][4][19000] = '--alt-preset standard';      // 3.90.3
				$KnownEncoderValues['**'][78][4][2][3][4][19000] = '--alt-preset fast standard'; // 3.90,   3.90.1, 3.90.2, 3.91, 3.92
				$KnownEncoderValues['**'][78][4][1][3][4][19000] = '--alt-preset fast standard'; // 3.90.3
				$KnownEncoderValues['**'][88][4][1][3][3][19500] = '--r3mix';                    // 3.90,   3.90.1, 3.92
				$KnownEncoderValues['**'][88][4][1][3][3][19600] = '--r3mix';                    // 3.90.2, 3.90.3, 3.91
				$KnownEncoderValues['**'][67][4][1][3][4][18000] = '--r3mix';                    // 3.94,   3.95
				$KnownEncoderValues['**'][68][3][2][3][4][18000] = '--alt-preset medium';        // 3.90.3
				$KnownEncoderValues['**'][68][4][2][3][4][18000] = '--alt-preset fast medium';   // 3.90.3

				$KnownEncoderValues[0xFF][99][1][1][1][2][0]     = '--preset studio';            // 3.90,   3.90.1, 3.90.2, 3.91, 3.92
				$KnownEncoderValues[0xFF][58][2][1][3][2][20600] = '--preset studio';            // 3.90.3, 3.93.1
				$KnownEncoderValues[0xFF][58][2][1][3][2][20500] = '--preset studio';            // 3.93
				$KnownEncoderValues[0xFF][57][2][1][3][4][20500] = '--preset studio';            // 3.94,   3.95
				$KnownEncoderValues[0xC0][88][1][1][1][2][0]     = '--preset cd';                // 3.90,   3.90.1, 3.90.2,   3.91, 3.92
				$KnownEncoderValues[0xC0][58][2][2][3][2][19600] = '--preset cd';                // 3.90.3, 3.93.1
				$KnownEncoderValues[0xC0][58][2][2][3][2][19500] = '--preset cd';                // 3.93
				$KnownEncoderValues[0xC0][57][2][1][3][4][19500] = '--preset cd';                // 3.94,   3.95
				$KnownEncoderValues[0xA0][78][1][1][3][2][18000] = '--preset hifi';              // 3.90,   3.90.1, 3.90.2,   3.91, 3.92
				$KnownEncoderValues[0xA0][58][2][2][3][2][18000] = '--preset hifi';              // 3.90.3, 3.93,   3.93.1
				$KnownEncoderValues[0xA0][57][2][1][3][4][18000] = '--preset hifi';              // 3.94,   3.95
				$KnownEncoderValues[0x80][67][1][1][3][2][18000] = '--preset tape';              // 3.90,   3.90.1, 3.90.2,   3.91, 3.92
				$KnownEncoderValues[0x80][67][1][1][3][2][15000] = '--preset radio';             // 3.90,   3.90.1, 3.90.2,   3.91, 3.92
				$KnownEncoderValues[0x70][67][1][1][3][2][15000] = '--preset fm';                // 3.90,   3.90.1, 3.90.2,   3.91, 3.92
				$KnownEncoderValues[0x70][58][2][2][3][2][16000] = '--preset tape/radio/fm';     // 3.90.3, 3.93,   3.93.1
				$KnownEncoderValues[0x70][57][2][1][3][4][16000] = '--preset tape/radio/fm';     // 3.94,   3.95
				$KnownEncoderValues[0x38][58][2][2][0][2][10000] = '--preset voice';             // 3.90.3, 3.93,   3.93.1
				$KnownEncoderValues[0x38][57][2][1][0][4][15000] = '--preset voice';             // 3.94,   3.95
				$KnownEncoderValues[0x38][57][2][1][0][4][16000] = '--preset voice';             // 3.94a14
				$KnownEncoderValues[0x28][65][1][1][0][2][7500]  = '--preset mw-us';             // 3.90,   3.90.1, 3.92
				$KnownEncoderValues[0x28][65][1][1][0][2][7600]  = '--preset mw-us';             // 3.90.2, 3.91
				$KnownEncoderValues[0x28][58][2][2][0][2][7000]  = '--preset mw-us';             // 3.90.3, 3.93,   3.93.1
				$KnownEncoderValues[0x28][57][2][1][0][4][10500] = '--preset mw-us';             // 3.94,   3.95
				$KnownEncoderValues[0x28][57][2][1][0][4][11200] = '--preset mw-us';             // 3.94a14
				$KnownEncoderValues[0x28][57][2][1][0][4][8800]  = '--preset mw-us';             // 3.94a15
				$KnownEncoderValues[0x18][58][2][2][0][2][4000]  = '--preset phon+/lw/mw-eu/sw'; // 3.90.3, 3.93.1
				$KnownEncoderValues[0x18][58][2][2][0][2][3900]  = '--preset phon+/lw/mw-eu/sw'; // 3.93
				$KnownEncoderValues[0x18][57][2][1][0][4][5900]  = '--preset phon+/lw/mw-eu/sw'; // 3.94,   3.95
				$KnownEncoderValues[0x18][57][2][1][0][4][6200]  = '--preset phon+/lw/mw-eu/sw'; // 3.94a14
				$KnownEncoderValues[0x18][57][2][1][0][4][3200]  = '--preset phon+/lw/mw-eu/sw'; // 3.94a15
				$KnownEncoderValues[0x10][58][2][2][0][2][3800]  = '--preset phone';             // 3.90.3, 3.93.1
				$KnownEncoderValues[0x10][58][2][2][0][2][3700]  = '--preset phone';             // 3.93
				$KnownEncoderValues[0x10][57][2][1][0][4][5600]  = '--preset phone';             // 3.94,   3.95
			}

			if (isset($KnownEncoderValues[$thisfile_mpeg_audio_lame['raw']['abrbitrate_minbitrate']][$thisfile_mpeg_audio_lame['vbr_quality']][$thisfile_mpeg_audio_lame['raw']['vbr_method']][$thisfile_mpeg_audio_lame['raw']['noise_shaping']][$thisfile_mpeg_audio_lame['raw']['stereo_mode']][$thisfile_mpeg_audio_lame['ath_type']][$thisfile_mpeg_audio_lame['lowpass_frequency']])) {

				$encoder_options = $KnownEncoderValues[$thisfile_mpeg_audio_lame['raw']['abrbitrate_minbitrate']][$thisfile_mpeg_audio_lame['vbr_quality']][$thisfile_mpeg_audio_lame['raw']['vbr_method']][$thisfile_mpeg_audio_lame['raw']['noise_shaping']][$thisfile_mpeg_audio_lame['raw']['stereo_mode']][$thisfile_mpeg_audio_lame['ath_type']][$thisfile_mpeg_audio_lame['lowpass_frequency']];

			} elseif (isset($KnownEncoderValues['**'][$thisfile_mpeg_audio_lame['vbr_quality']][$thisfile_mpeg_audio_lame['raw']['vbr_method']][$thisfile_mpeg_audio_lame['raw']['noise_shaping']][$thisfile_mpeg_audio_lame['raw']['stereo_mode']][$thisfile_mpeg_audio_lame['ath_type']][$thisfile_mpeg_audio_lame['lowpass_frequency']])) {

				$encoder_options = $KnownEncoderValues['**'][$thisfile_mpeg_audio_lame['vbr_quality']][$thisfile_mpeg_audio_lame['raw']['vbr_method']][$thisfile_mpeg_audio_lame['raw']['noise_shaping']][$thisfile_mpeg_audio_lame['raw']['stereo_mode']][$thisfile_mpeg_audio_lame['ath_type']][$thisfile_mpeg_audio_lame['lowpass_frequency']];

			} elseif ($info['audio']['bitrate_mode'] == 'vbr') {

				// http://gabriel.mp3-tech.org/mp3infotag.html
				// int    Quality = (100 - 10 * gfp->VBR_q - gfp->quality)h
				$LAME_V_value = 10 - ceil($thisfile_mpeg_audio_lame['vbr_quality'] / 10);
				$LAME_q_value = 100 - $thisfile_mpeg_audio_lame['vbr_quality'] - ($LAME_V_value * 10);
				$encoder_options = '-V' . $LAME_V_value . ' -q' . $LAME_q_value;
			} elseif ($info['audio']['bitrate_mode'] == 'cbr') {

				$encoder_options = strtoupper($info['audio']['bitrate_mode']).ceil($info['audio']['bitrate'] / 1000);
			} else {

				$encoder_options = strtoupper($info['audio']['bitrate_mode']);
			}

		} elseif (!empty($thisfile_mpeg_audio_lame['bitrate_abr'])) {

			$encoder_options = 'ABR' . $thisfile_mpeg_audio_lame['bitrate_abr'];

		} elseif (!empty($info['audio']['bitrate'])) {

			if ($info['audio']['bitrate_mode'] == 'cbr') {
				$encoder_options = strtoupper($info['audio']['bitrate_mode']).ceil($info['audio']['bitrate'] / 1000);
			} else {
				$encoder_options = strtoupper($info['audio']['bitrate_mode']);
			}
		}
		if (!empty($thisfile_mpeg_audio_lame['bitrate_min'])) {
			$encoder_options .= ' -b'.$thisfile_mpeg_audio_lame['bitrate_min'];
		}

		if (!empty($thisfile_mpeg_audio_lame['encoding_flags']['nogap_prev']) || !empty($thisfile_mpeg_audio_lame['encoding_flags']['nogap_next'])) {
			$encoder_options .= ' --nogap';
		}

		if (!empty($thisfile_mpeg_audio_lame['lowpass_frequency'])) {
			$ExplodedOptions = explode(' ', $encoder_options, 4);
			if ($ExplodedOptions[0] == '--r3mix') {
				$ExplodedOptions[1] = 'r3mix';
			}
			switch ($ExplodedOptions[0]) {
				case '--preset':
				case '--alt-preset':
				case '--r3mix':
					if ($ExplodedOptions[1] == 'fast') {
						$ExplodedOptions[1] .= ' ' . $ExplodedOptions[2];
					}
					switch ($ExplodedOptions[1]) {
						case 'portable':
						case 'medium':
						case 'standard':
						case 'extreme':
						case 'insane':
						case 'fast portable':
						case 'fast medium':
						case 'fast standard':
						case 'fast extreme':
						case 'fast insane':
						case 'r3mix':
							static $ExpectedLowpass = [
									'insane|20500'        => 20500,
									'insane|20600'        => 20600,  // 3.90.2, 3.90.3, 3.91
									'medium|18000'        => 18000,
									'fast medium|18000'   => 18000,
									'extreme|19500'       => 19500,  // 3.90,   3.90.1, 3.92, 3.95
									'extreme|19600'       => 19600,  // 3.90.2, 3.90.3, 3.91, 3.93.1
									'fast extreme|19500'  => 19500,  // 3.90,   3.90.1, 3.92, 3.95
									'fast extreme|19600'  => 19600,  // 3.90.2, 3.90.3, 3.91, 3.93.1
									'standard|19000'      => 19000,
									'fast standard|19000' => 19000,
									'r3mix|19500'         => 19500,  // 3.90,   3.90.1, 3.92
									'r3mix|19600'         => 19600,  // 3.90.2, 3.90.3, 3.91
									'r3mix|18000'         => 18000,  // 3.94,   3.95
								];
							if (!isset($ExpectedLowpass[$ExplodedOptions[1] . '|' . $thisfile_mpeg_audio_lame['lowpass_frequency']]) && ($thisfile_mpeg_audio_lame['lowpass_frequency'] < 22050) && (round($thisfile_mpeg_audio_lame['lowpass_frequency'] / 1000) < round($thisfile_mpeg_audio['sample_rate'] / 2000))) {
								$encoder_options .= ' --lowpass '.$thisfile_mpeg_audio_lame['lowpass_frequency'];
							}
							break;

						default:
							break;
					}
					break;
			}
		}

		if (isset($thisfile_mpeg_audio_lame['raw']['source_sample_freq'])) {
			if ($thisfile_mpeg_audio['sample_rate'] == 44100 && $thisfile_mpeg_audio_lame['raw']['source_sample_freq'] != 1) {
				$encoder_options .= ' --resample 44100';
			} elseif ($thisfile_mpeg_audio['sample_rate'] == 48000 && $thisfile_mpeg_audio_lame['raw']['source_sample_freq'] != 2) {
				$encoder_options .= ' --resample 48000';
			} elseif ($thisfile_mpeg_audio['sample_rate'] < 44100) {
				switch ($thisfile_mpeg_audio_lame['raw']['source_sample_freq']) {
					case 0: // <= 32000
						// may or may not be same as source frequency - ignore
						break;
					case 1: // 44100
					case 2: // 48000
					case 3: // 48000+
						$ExplodedOptions = explode(' ', $encoder_options, 4);
						switch ($ExplodedOptions[0]) {
							case '--preset':
							case '--alt-preset':
								switch ($ExplodedOptions[1]) {
									case 'fast':
									case 'portable':
									case 'medium':
									case 'standard':
									case 'extreme':
									case 'insane':
										$encoder_options .= ' --resample ' . $thisfile_mpeg_audio['sample_rate'];
										break;

									default:
										static $ExpectedResampledRate = [
											'phon+/lw/mw-eu/sw|16000' => 16000,
											'mw-us|24000'             => 24000, // 3.95
											'mw-us|32000'             => 32000, // 3.93
											'mw-us|16000'             => 16000, // 3.92
											'phone|16000'             => 16000,
											'phone|11025'             => 11025, // 3.94a15
											'radio|32000'             => 32000, // 3.94a15
											'fm/radio|32000'          => 32000, // 3.92
											'fm|32000'                => 32000, // 3.90
											'voice|32000'             => 32000
										];
										if (!isset($ExpectedResampledRate[$ExplodedOptions[1] . '|' . $thisfile_mpeg_audio['sample_rate']])) {
											$encoder_options .= ' --resample ' . $thisfile_mpeg_audio['sample_rate'];
										}
										break;
								}
								break;

							case '--r3mix':
							default:
								$encoder_options .= ' --resample ' . $thisfile_mpeg_audio['sample_rate'];
								break;
						}
						break;
				}
			}
		}
		if (empty($encoder_options) && !empty($info['audio']['bitrate']) && !empty($info['audio']['bitrate_mode'])) {
			$encoder_options = strtoupper($info['audio']['bitrate_mode']);
		}

		return $encoder_options;
	}
}
