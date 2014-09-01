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
