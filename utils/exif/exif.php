<?php

namespace Swimmer\Utils\Exif;

/**
 * @author Jonas Edemalm (http://www.retype.se)
 * @version 0.1
 * @see http://www.quietless.com/kitchen/extract-exif-data-using-php-to-display-gps-tagged-images-in-google-maps/
 */
class Exif 
{
	/**
	 * @param string $full_image_path
	 * @return array
	 */
	public function get_gps_data(string $full_image_path): array
	{
		$raw_exif = exif_read_data($full_image_path, 0, true);

	    if (isset($raw_exif['GPS']['GPSLatitude']) && isset($raw_exif['GPS']['GPSLongitude'])) { 
	       
			$lat = $raw_exif['GPS']['GPSLatitude']; 
		    $log = $raw_exif['GPS']['GPSLongitude'];
	       		
	       	// Latitude values
	       	$lat_degrees = $this->divide($lat[0]);
	       	$lat_minutes = $this->divide($lat[1]);
	       	$lat_seconds = $this->divide($lat[2]);
	       	$lat_hemi = $raw_exif['GPS']['GPSLatitudeRef'];
 
	       	// Longitude values
	       	$log_degrees = $this->divide($log[0]);
	       	$log_minutes = $this->divide($log[1]);
	       	$log_seconds = $this->divide($log[2]);
	       	$log_hemi = $raw_exif['GPS']['GPSLongitudeRef'];
	  		 
	       	$data = [
	       		'lat_decimal' => $this->toDecimal($lat_degrees, $lat_minutes, $lat_seconds, $lat_hemi),
	       		'lon_decimal' => $this->toDecimal($log_degrees, $log_minutes, $log_seconds, $log_hemi)
	       	];
	    } 
	    else {
			$data = [
				'lat_decimal' => 'null',
		    	'lon_decimal' => 'null'
		    ];
	    }
	       
	    return $data;
	}

	/**
	 * @param string $full_image_path
	 * @return array
	 */
	public function get_exif_info(string $full_image_path): array
	{
		$raw_exif = exif_read_data($full_image_path, 0, true);

		return [
			'make' 			=> $raw_exif["IFD0"]["Make"],
			'model' 		=> $raw_exif["IFD0"]["Model"],
			'date_time' 	=> $raw_exif["EXIF"]["DateTimeOriginal"],
			'exposure_time' => $raw_exif["EXIF"]["ExposureTime"],
			'f_number' 		=> $raw_exif["EXIF"]["FNumber"],
			'iso_speed' 	=> $raw_exif["EXIF"]["ISOSpeedRatings"],
			'shutter_speed' => $raw_exif["EXIF"]["ShutterSpeedValue"]  
	    ];
	}

	/**
	 * @param string $a
	 * @preturn float
	 */
	private function divide(string $a): float
	{
		// Evaluate the string fraction and return a float
		$e = explode('/', $a);

		// Prevent division by zero 
		if (!$e[0] || !$e[1]) {
			return 0;
		} else {
			return $e[0] / $e[1];
		}
	}

	/**
	 * @param float $deg
	 * @param float $min
	 * @param float $sec
	 * @param string $hemi
	 * @return float
	 */
	private function toDecimal(float $deg, float $min, float $sec, string $hemi): float
	{
    	$d = $deg + $min / 60 + $sec / 3600;
    	return ($hemi == 'S' || $hemi == 'W') ? $d *= -1 : $d;
    }
}
