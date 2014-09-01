<?php
	require('functions.php');
	
	$ctab = makeColourMap();

	$c = 0;
	$volgroups = array();
	$data = getTable('/sbin/vgs -o +vg_extent_size,vg_extent_count,vg_free_count', array('VG','#PV','#LV','#SN','Attr','VSize','VFree','Ext','#Ext','Free'));
	foreach($data as $row)
	{
		$row['devices'] = array();
		$row['volumes'] = array();
		$volgroups[$row['VG']] = $row;
	}

	$data = getTable('/sbin/pvs -o +pv_pe_count,pv_pe_alloc_count', array('PV','VG','Fmt','Attr','PSize','PFree','PE','Alloc'));
	foreach($data as $row)
	{
		$row['segments'] = array();
		$volgroups[$row['VG']]['devices'][$row['PV']] = $row;
	}

	$data = getTable('/sbin/lvs --segments -o +seg_pe_ranges -a', array('LV','VG','Attr','#Str','Type','SSize','PE Ranges'));
	foreach($data as $row)
	{
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

	require('display.php');
?>
