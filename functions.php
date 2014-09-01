<?php
	function makeColourMap()
	{
		$ctab = array();
		$loop = 1;
		while ($loop < 3)
		{
			$cH = 0;
			while ($cH < 360)
			{
				list($r, $g, $b) = hsv2Rgb($cH, 50 * $loop, 100);
				$ctab[] = rgb2Hex($r, $g, $b);
				$cH += 20;
			}
			$loop++;
		}
		return $ctab;
	}

	function hsv2Rgb($iH, $iS, $iV)
	{
		if($iH < 0) $iH = 0; // Hue:
		if($iH > 360) $iH = 360; // 0-360
		if($iS < 0) $iS = 0; // Saturation:
		if($iS > 100) $iS = 100; // 0-100
		if($iV < 0) $iV = 0; // Lightness:
		if($iV > 100) $iV = 100; // 0-100

		$dS = $iS/100.0; // Saturation: 0.0-1.0
		$dV = $iV/100.0; // Lightness: 0.0-1.0
		$dC = $dV*$dS; // Chroma: 0.0-1.0
		$dH = $iH/60.0; // H-Prime: 0.0-6.0
		$dT = $dH; // Temp variable

		while($dT >= 2.0) $dT -= 2.0;
		$dX = $dC*(1-abs($dT-1)); // as used in the Wikipedia link

		switch($dH)
		{
			case($dH >= 0.0 && $dH < 1.0): $dR = $dC; $dG = $dX; $dB = 0.0; break;
			case($dH >= 1.0 && $dH < 2.0): $dR = $dX; $dG = $dC; $dB = 0.0; break;
			case($dH >= 2.0 && $dH < 3.0): $dR = 0.0; $dG = $dC; $dB = $dX; break;
			case($dH >= 3.0 && $dH < 4.0): $dR = 0.0; $dG = $dX; $dB = $dC; break;
			case($dH >= 4.0 && $dH < 5.0): $dR = $dX; $dG = 0.0; $dB = $dC; break;
			case($dH >= 5.0 && $dH < 6.0): $dR = $dC; $dG = 0.0; $dB = $dX; break;
			default: $dR = 0.0; $dG = 0.0; $dB = 0.0; break;
		}

		$dM = $dV - $dC;
		$dR += $dM; $dG += $dM; $dB += $dM;
		$dR *= 255; $dG *= 255; $dB *= 255;

		return array(round($dR), round($dG), round($dB));
	}
	
	function rgb2Hex($R, $G, $B)
	{ 
		$R=dechex($R);
		if (strlen($R)<2) $R='0'.$R;
		
		$G=dechex($G);
		if (strlen($G)<2) $G='0'.$G;

		$B=dechex($B);
		if (strlen($B)<2) $B='0'.$B;

		return '#' . $R . $G . $B;
	}
	
	function getTable($command, $columns)
	{
		$data = array();
		exec('sudo '.$command, &$data);
		$map = getmap($columns, $data[0]);
		$rows = array();
		for($i = 1; $i < count($data); ++$i)
		{
			$rows[] = getrow($map, $data[$i]);
		}
		return $rows;
	}

	function getmap($cols, $header)
	{
		$map = array();
		$pos = 0;
		$pcol = null;
		foreach($cols as $col)
		{
			$pos = strpos($header, $col, $pos);
			$map[$col] = array('start' => $pos);
			if($pcol != null)
				$map[$pcol]['end'] = $pos - 1;
			$pcol = $col;
		}
		return $map;
	}

	function getrow($map, $line)
	{
		$row = array();
		foreach($map as $col => $range)
			$row[$col] = trim(isset($range['end']) ?  substr($line, $range['start'], $range['end'] - $range['start']) : substr($line, $range['start']));
		return $row;
	}
?>
