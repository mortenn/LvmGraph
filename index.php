<?php
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
	
	$color_list = Array();
	
	$loop = 1;
	$ctab = array();
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
	//$ctab = array('#5500ff','#aaee00','#00eeff','#bbeeee','#bbffee','#ccffee','#ccffdd','#ff5500');
	$c = 0;
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
	$data = null;
	exec('sudo /sbin/vgs -o +vg_extent_size,vg_extent_count,vg_free_count', &$data);
	$cols = array('VG','#PV','#LV','#SN','Attr','VSize','VFree','Ext','#Ext','Free');
	$map = getmap($cols, $data[0]);
	$volgroups = array();
	for($i = 1; $i < count($data); ++$i)
	{
		$row = getrow($map, $data[$i]);
		$row['devices'] = array();
		$row['volumes'] = array();
		$volgroups[$row['VG']] = $row;
	}
	$data = null;
	exec('sudo /sbin/pvs -o +pv_pe_count,pv_pe_alloc_count', &$data);
	$cols = array('PV','VG','Fmt','Attr','PSize','PFree','PE','Alloc');
	$map = getmap($cols, $data[0]);
	for($i = 1; $i < count($data); ++$i)
	{
		$row = getrow($map, $data[$i]);
		$row['segments'] = array();
		$volgroups[$row['VG']]['devices'][$row['PV']] = $row;
	}
	$data = null;
	exec('sudo /sbin/lvs --segments -o +seg_pe_ranges -a', &$data);
	$cols = array('LV','VG','Attr','#Str','Type','SSize','PE Ranges');
	$map = getmap($cols, $data[0]);
	for($i = 1; $i < count($data); ++$i)
	{
		$row = getrow($map, $data[$i]);
		$ranges = array();
		foreach(split(' ', $row['PE Ranges']) as $pe)
		{
			$device = split(':',$pe);
			if(isset($volgroups[$row['VG']]['devices'][$device[0]]))
				$volgroups[$row['VG']]['devices'][$device[0]]['segments'][] = $row['LV'];
			$range = split('-',$device[1]);
			$ranges[$pe] = array(
				'device' => $device[0],
				'start' => $range[0],
				'end' => $range[1]
			);
		}
		$row['PE Ranges'] = $ranges;
		if(!isset($volgroups[$row['VG']]['volumes'][$row['LV']]))
		{
			$volgroups[$row['VG']]['volumes'][$row['LV']] = array('LV' => $row['LV'], 'color' => $ctab[$c++%count($ctab)], 'segments'=>array());
		}
		$volgroups[$row['VG']]['volumes'][$row['LV']]['segments'][] = $row;
	}
?>
<!doctype html>
<html>
	<head>
		<style>
			div.segment:hover { background: silver !important; }
		</style>
	</head>
	<body>
<?php
	$w = 800;
	foreach($volgroups as $volgroup)
	{
		$scale = $volgroup['#Ext'] / 5000;
?>
		<div>
			<h1><?php echo $volgroup['VG']; ?></h1>
<?php
		foreach($volgroup['devices'] as $device)
		{
			$pend = 0;
			echo '<div style="clear:both">'.$device['PV'].' '.$device['PSize'].'</div>';
			$width = $device['PE'] / $scale;
?>
			<div style="width:<?php echo $width; ?>px; height:20px;background:black;">
<?php
			$segs = array();
			foreach($volgroup['volumes'] as $logvol)
				foreach($logvol['segments'] as $segment)
					foreach($segment['PE Ranges'] as $per)
						if($per['device'] == $device['PV'])
							$segs[$per['start']] = array(
								'color' => $logvol['color'],
								'scale' => $width / $device['PE'],
								'width' => round(($width / $device['PE']) * ($per['end'] - $per['start'])),
								'LV' => $logvol['LV'],
								'end' => $per['end']
							);
			ksort($segs);
			foreach($segs as $start => $seg)
			{
				printf('<div class="segment" style="float:left;background:%s;height:20px;width:%dpx;margin-left:%dpx" title="%s"></div>',
					$seg['color'],
					$seg['width'],
					$seg['end'] - $pend > 0 ? round($seg['scale'] * ($start - $pend)) : 0,
					$seg['LV']
				);
				$pend = $seg['end'];
			}
?>
			</div>
<?php
		}
		foreach($volgroup['volumes'] as $volume => $logvol)
		{
?>
			<div>
				<h2><div style="background:<?php echo $logvol['color']; ?>;float:left">&nbsp;</div><?php echo $volume; ?></h2>
				<table>
<?php
			foreach($logvol['segments'] as $segment)
			{
?>
				<tr>
					<td><?php echo $segment['Type']; ?></td>
					<td><?php echo $segment['SSize']; ?></td>
					<td><?php echo join(', ', array_keys($segment['PE Ranges'])); ?></td>
				</tr>
<?php
			}
?>
				</table>
			</div>
<?php
		}
?>
		</div>
<?php
	}
?>
	</body>
</html>
