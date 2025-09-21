<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN"
		"http://www.w3.org/TR/html4/strict.dtd">
<html lang="en">
<head>
	<meta http-equiv="content-type" content="text/html; charset=utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=9" />
	<title>Admin | Contributors | Macaw</title>
		<link rel="stylesheet" type="text/css" href="/css/yui-combo.css"> 
	<?php $this->load->view('global/head_view') ?>
</head>
<body class="yui-skin-sam">
	<?php $this->load->view('global/header_view') ?>
	<div id="orglist">
		<h1>Monthly Report</h1>
		<br>
		
		<?php if (count($results)) { ?>
		<table width="100%">
			<tr>
				<th>Month</th>
				<th>Contributor</th>
				<th># of Items</th>
				<th># of Pages</th>
			</tr>
			<?php $curr_month = $results[0]['month']; ?>
			<?php foreach ($results as $r) { ?>
				<?php if (!$local_admin && $curr_month != $r['month']) { ?>
					<tr>
						<td colspan="4"></td>
					</tr>
				<?php $curr_month = $r['month']; ?>
			<?php } ?>
			<tr>
				<td><?php echo($r['month']); ?></td>
				<td><?php echo($r['contributor']); ?></td>
				<td><?php echo($r['items']); ?></td>
				<td><?php echo($r['pages']); ?></td>
			</tr>
			<?php } ?>
		</table>
		<?php } ?>
	</div>	
	<?php $this->load->view('global/footer_view') ?>
</body>
</html>
