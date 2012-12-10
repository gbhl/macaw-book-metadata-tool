<?php
	include_once('system/application/config/version.php');
	$cfg = $this->config->item('macaw');
	$is_admin = $this->user->has_permission('admin') || $this->user->has_permission('local_admin');	
	$can_edit_item = false;
	$barcode = $this->session->userdata('barcode');
	if ($barcode) {
		if ($this->book->exists($barcode)) {
			$this->book->load($barcode);
			$status = $this->book->status;		
			if (in_array($status, array('new','scanning','scanned','reviewing','reviewed'))) {
				$can_edit_item = true;
			}
		} else {
			$this->session->set_userdata('barcode', '');
			$this->session->set_userdata('title', '');
			$this->session->set_userdata('author', '');			
		}
	}
?>

<?php if ($this->uri->total_segments() == 0 || $this->uri->segment(1) == 'login') { ?>
	<div id="doc3">
		<div id="bd">
<?php } else { ?>
	<div id="doc3">
		<div id="banner">
			<div id="hd">
				<div id="title" style="float: left">
					<img src="<?php echo $this->config->item('base_url'); ?>images/logo.png" alt="logo.png" width="22" height="22" border="0" align="left" id="logo">
					<div style="float:left">
					<h4 style="padding-right:10px;">Macaw Metadata Collection and Workflow System <?php if ($cfg['testing']) { echo(' <span style="color:#F90;">&nbsp;&nbsp;&nbsp;&nbsp;DEVELOPMENT VERSION</span>'); }?></h4>
					</div>
					<div class="clear"><!-- --></div>
				</div>
				<?php $this->load->view('global/pagetop_view') ?>
			</div>
		</div>
		<!-- Added main menu view --> 
		<div id="main-menu"> 
			<ul>
				<li class="top"><a href="/dashboard">Dashboard</a></li>
				<li class="top"><a href="/main/listitems">In Progress</a></li>
				<li class="top"><a href="#create-new">Create New</a>
					<ul>
						<li><a href="/main/add"><img src="/images/01_manually_ad_icon_sm.png">Manually Add Item</a></li>
						<li class="last"><a href="/main/import"><img src="/images/01_manually_ad_icon_sm.png">Create from CSV</a></li>
					</ul>
				</li>
				<li class="top"><a href="#manage">Current Item</a>
					<ul>					
						<?php if ($can_edit_item) { ?>
						<li><a href="/scan/upload/"><img src="/images/icon-upload-small.png">Upload Files</a></li>
						<li><a href="/scan/monitor/"><img src="/images/03_import_pages_icon_sm.png">Import Pages</a></li>
						<li><a href="/scan/review"><img src="/images/04_review_pages_icon_sm.png">Review Pages</a></li>
						<li><a href="/scan/missing/insert"><img src="/images/05_insert_missing_pages_icon_sm.png">Insert Missing Pages</a></li>
						<li><a href="/main/edit"><img src="/images/07_edit_items_icon_sm.png">Edit Item</a></li>						
						<?php } else { ?>
						<li class="disabled"><a href="#"><img src="/images/03_import_pages_icon_sm.png">Upload Pages</a></li>
						<li class="disabled"><a href="#"><img src="/images/03_import_pages_icon_sm.png">Import Pages</a></li>
						<li class="disabled"><a href="#"><img src="/images/04_review_pages_icon_sm.png">Review Pages</a></li>
						<li class="disabled"><a href="#"><img src="/images/05_insert_missing_pages_icon_sm.png">Insert Missing Pages</a></li>
						<li class="disabled"><a href="#"><img src="/images/07_edit_items_icon_sm.png">Edit Item</a></li>						
						<?php } ?>
						<?php if ($can_edit_item) { ?>
						<li class="last"><a href="/scan/history"><img src="/images/06_view_history_icon_sm.png">View History</a></li>
						<?php } else { ?>
						<li class="last disabled"><a href="#"><img src="/images/06_view_history_icon_sm.png">View History</a></li>
						<?php } ?>
					</ul>
				</li>
				<?php if ($is_admin) { ?>
				<li class="top"><a href="#admin">Admin</a>
					<ul>
						<li><a href="/admin/account"><img src="/images/08_list_accounts_icon_sm.png">List Accounts</a></li>
						<li><a href="/admin/queues"><img src="/images/09_queues_icon_sm.png">Queues</a></li>
						<li><a href="/admin/logs"><img src="/images/10_view_logs_icon_sm.png">View Logs</a></li>
						<li><a href="/admin/organization"><img src="/images/11_organisations_icon_sm.png">Organizations</a></li>
						<li class="last"><a href="/admin/scheduled_jobs"><img src="/images/12_manually_run_icon_sm.png">Scheduled Jobs</a></li>
					</ul>
				</li>
				<?php } ?>
			</ul>            
		</div>
		<?php if (isset($item_title)) { ?>
			<div id="item-title">
				<?php echo($item_title); ?>
			</div>
		<?php } ?>
		
		<div class="messagediv">
			<?php $this->load->view('global/error_messages_view') ?>
		</div>	
		<div id="bd">
<?php } ?>
