<?php
  /**************************************************************************\
  * phpGroupWare - addressbook                                               *
  * http://www.phpgroupware.org                                              *
  * Written by Joseph Engo <jengo@phpgroupware.org>                          *
  * --------------------------------------------                             *
  *  This program is free software; you can redistribute it and/or modify it *
  *  under the terms of the GNU General Public License as published by the   *
  *  Free Software Foundation; either version 2 of the License, or (at your  *
  *  option) any later version.                                              *
  \**************************************************************************/

  /* $Id$ */

	if ($download == 'on')
	{
		$phpgw_info['flags'] = array(
			'noheader' => True,
			'nonavbar' => True
		);
	}
	else
	{
		$phpgw_info['flags'] = array(
			'noheader' => False,
			'nonavbar' => False
		);
	}

	$phpgw_info['flags']['currentapp'] = 'addressbook';
	$phpgw_info['flags']['enable_contacts_class'] = True;
	$phpgw_info['flags']['enable_browser_class'] = True;
	include('../header.inc.php');

	if (!$convert)
	{
		$t = new Template(PHPGW_APP_TPL);
		$t->set_file(array('export' => 'export.tpl'));

		$dir_handle=opendir(PHPGW_APP_ROOT . SEP . 'export');
		$i=0; $myfilearray='';
		while ($file = readdir($dir_handle))
		{
			#echo "<!-- ".is_file($phpgw_info["server"]["app_root"].$sep."conv".$sep.$file)." -->";
			if ((substr($file, 0, 1) != '.') && is_file(PHPGW_APP_ROOT . SEP . 'export' . SEP . $file) )
			{
				$myfilearray[$i] = $file;
				$i++;
			}
		}
		closedir($dir_handle);
		sort($myfilearray);
		for ($i=0;$i<count($myfilearray);$i++)
		{
			$fname = ereg_replace('_',' ',$myfilearray[$i]);
			$conv .= '        <option value="'.$myfilearray[$i].'">'.$fname.'</option>'."\n";
		}

		$t->set_var('lang_cancel',lang('Cancel'));
		$t->set_var('lang_cat',lang('Select Category'));
		$t->set_var('cat_link',cat_option($cat_id,False,False));
		$t->set_var('cancel_url',$phpgw->link('/addressbook/index.php'));
		$t->set_var('navbar_bg',$phpgw_info['theme']['navbar_bg']);
		$t->set_var('navbar_text',$phpgw_info['theme']['navbar_text']);
		$t->set_var('export_text',lang('Export from Addressbook'));
		$t->set_var('action_url',$phpgw->link('/addressbook/export.php'));
		$t->set_var('filename',lang('Export file name'));
		$t->set_var('conv',$conv);
		$t->set_var('debug',lang(''));
		$t->set_var('download',lang('Submit'));
		$t->set_var('start',$start);
		$t->set_var('sort',$sort);
		$t->set_var('order',$order);
		$t->set_var('filter',$filter);
		$t->set_var('query',$query);
		$t->set_var('cat_id',$cat_id);
		$t->pparse('out','export');

		$phpgw->common->phpgw_footer();
	}
	else
	{
		if ($conv_type == 'none')
		{
			$phpgw_info['flags']['noheader'] = False;
			$phpgw_info['flags']['noheader'] = True;
			$phpgw->common->phpgw_header();
			echo parse_navbar();
			echo lang('<b>No conversion type &lt;none&gt; could be located.</b>  Please choose a conversion type from the list');
			echo '&nbsp<a href="'.$phpgw->link('/addressbook/export.php',
				"sort=$sort&order=$order&filter=$filter&start=$start&query=$query&cat_id=$cat_id")
				. '">'.lang('OK').'</a>';
			$phpgw->common->phpgw_footer();
			$phpgw->common->phpgw_exit();
		}
		else
		{
			include (PHPGW_APP_ROOT . SEP . 'export' . SEP . $conv_type);
			$buffer=array();
			$contacts = new export_conv;

			// Read in user custom fields, if any
			$customfields = array();
			while (list($col,$descr) = @each($phpgw_info['user']['preferences']['addressbook']))
			{
			if ( substr($col,0,6) == 'extra_' )
				{
					$field = ereg_replace('extra_','',$col);
						$field = ereg_replace(' ','_',$field);
					$customfields[$field] = ucfirst($field);
				}
			}
 			$extrafields = array(
				'ophone'   => 'ophone',
				'address2' => 'address2',
				'address3' => 'address3'
			);
			if ($contacts->type != 'vcard')
			{
				$contacts->qfields = $contacts->stock_contact_fields;# + $extrafields;# + $customfields;
			}

			if (!empty($cat_id))
			{
				$buffer = $contacts->export_start_file($buffer,$cat_id);
			}
			else
			{
			$buffer = $contacts->export_start_file($buffer);
			}

			for ($i=0;$i<count($contacts->ids);$i++)
			{
				$buffer = $contacts->export_start_record($buffer);
				while( list($name,$value) = each($contacts->currentrecord) )
				{
					$buffer = $contacts->export_new_attrib($buffer,$name,$value);
				}
				$buffer = $contacts->export_end_record($buffer);
			}

			// Here, buffer becomes a string suitable for printing
			$buffer = $contacts->export_end_file($buffer);

			$tsvfilename = $phpgw_info['server']['temp_dir'].$sep.$tsvfilename;
		}

		if ( ($download == 'on') || ($o->type == 'pdb') )
		{
			// filename, default application/octet-stream, length of file, default nocache True
			$phpgw->browser->content_header($tsvfilename,'application/octet-stream',strlen($buffer));
			echo $buffer;
		}
		else
		{
			echo "<pre>\n";
			echo $buffer;
			echo "\n</pre>\n";
			echo '<a href="'.$phpgw->link('/addressbook/index.php',
				"sort=$sort&order=$order&filter=$filter&start=$start&query=$query&cat_id=$cat_id")
				. '">'.lang('OK').'</a>';
			$phpgw->common->phpgw_footer();
		}
	}
?>
