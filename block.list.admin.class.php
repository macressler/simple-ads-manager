<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if(!class_exists('SamBlockList')) {
  class SamBlockList {
    private $settings = array();
    
    public function __construct($settings) {
      $this->settings = $settings;
    }
    
    public function page() {
      global $wpdb;
      $bTable = $wpdb->prefix . "sam_blocks";
      
      if(isset($_GET['mode'])) $mode = sanitize_text_field($_GET['mode']);
      else $mode = 'active';
      if(isset($_GET["action"])) $action = sanitize_text_field($_GET['action']);
      else $action = 'blocks';
      if(isset($_GET['item'])) $item = (int)$_GET['item'];
      else $item = null;
      if(isset($_GET['iaction'])) $iaction = sanitize_text_field($_GET['iaction']);
      else $iaction = null;
      if(isset($_GET['iitem'])) $iitem = (int)$_GET['iitem'];
      else $iitem = null;
      if(isset($_GET['apage'])) $apage = abs( (int) $_GET['apage'] );
      else $apage = 1;

      $options = $this->settings;
      $places_per_page = $options['placesPerPage'];
      $items_per_page = $options['itemsPerPage'];
      
      if(!is_null($item)) {
        if($iaction === 'delete') $wpdb->update( $bTable, array( 'trash' => true ), array( 'id' => $item ), array( '%d' ), array( '%d' ) );
        elseif($iaction === 'untrash') $wpdb->update( $bTable, array( 'trash' => false ), array( 'id' => $item ), array( '%d' ), array( '%d' ) );
        elseif($iaction === 'kill') $wpdb->query($wpdb->prepare("DELETE FROM $bTable WHERE id=%d", $item));
      }
      if($iaction === 'kill-em-all') $wpdb->query("DELETE FROM $bTable WHERE trash=true");
      $trash_num = $wpdb->get_var("SELECT COUNT(*) FROM $bTable WHERE trash = TRUE");
      $active_num = $wpdb->get_var("SELECT COUNT(*) FROM $bTable WHERE trash = FALSE");
      if(is_null($active_num)) $active_num = 0;
      if(is_null($trash_num)) $trash_num = 0;
      $all_num = $trash_num + $active_num;
      $total = (($mode !== 'all') ? (($mode === 'trash') ? $trash_num : $active_num) : $all_num);
      $start = $offset = ( $apage - 1 ) * $places_per_page;

      $page_links = paginate_links( array(
        'base' => admin_url('admin.php') . '?page=sam-block-list&apage=%#%',
        'format' => '&apage=%#%',
        'prev_text' => __('&laquo;'),
        'next_text' => __('&raquo;'),
        'total' => ceil($total / $places_per_page),
        'current' => $apage
      ));
      ?>
<div class='wrap'>
  <div class="icon32" style="background: url('<?php echo SAM_IMG_URL.'sam-list.png' ?>') no-repeat transparent; "><br/></div>
  <h2><?php _e('Managing Ads Blocks', SAM_DOMAIN); ?></h2>
  <?php
    include_once('errors.class.php');
    $errors = new samErrors();
    if(!empty($errors->errorString)) echo $errors->errorString;
  ?>
  <ul class="subsubsub">
    <li><a <?php if($mode === 'all') echo 'class="current"';?> href="<?php echo admin_url('admin.php'); ?>?page=sam-block-list&action=blocks&mode=all"><?php _e('All', SAM_DOMAIN); ?></a> (<?php echo $all_num; ?>) | </li>
    <li><a <?php if($mode === 'active') echo 'class="current"';?> href="<?php echo admin_url('admin.php'); ?>?page=sam-block-list&action=blocks&mode=active"><?php _e('Active', SAM_DOMAIN); ?></a> (<?php echo $active_num; ?>) | </li>
    <li><a <?php if($mode === 'trash') echo 'class="current"';?> href="<?php echo admin_url('admin.php'); ?>?page=sam-block-list&action=blocks&mode=trash"><?php _e('Trash', SAM_DOMAIN); ?></a> (<?php echo $trash_num; ?>)</li>
  </ul>
  <div class="tablenav">
    <div class="alignleft">
      <?php if($mode === 'trash') {?>
      <a class="button-secondary" href="<?php echo admin_url('admin.php'); ?>?page=sam-block-list&action=blocks&mode=trash&iaction=kill-em-all"><?php _e('Clear Trash', SAM_DOMAIN); ?></a>
      <?php } else { ?>
      <a class="button-secondary" href="<?php echo admin_url('admin.php'); ?>?page=sam-block-edit&action=new&mode=block"><?php _e('Add New Block', SAM_DOMAIN); ?></a>
      <?php } ?>
    </div>
    <div class="tablenav-pages">
      <?php $page_links_text = sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s of %s', SAM_DOMAIN ) . '</span>%s',
        number_format_i18n( $start + 1 ),
        number_format_i18n( min( $apage * $places_per_page, $total ) ),
        '<span class="total-type-count">' . number_format_i18n( $total ) . '</span>',
        $page_links
      ); echo $page_links_text; ?>
    </div>
  </div>
  <div class="clear"></div>
  <table class="widefat fixed" cellpadding="0">
    <thead>
      <tr>
        <th id="t-idg" class="manage-column column-title" style="width:5%;" scope="col"><?php _e('ID', SAM_DOMAIN); ?></th>
        <th id="t-name" class="manage-column column-title" style="width:95%;" scope="col"><?php _e('Block Name', SAM_DOMAIN);?></th>        
      </tr>
    </thead>
    <tfoot>
      <tr>
        <th id="b-idg" class="manage-column column-title" style="width:5%;" scope="col"><?php _e('ID', SAM_DOMAIN); ?></th>
        <th id="b-name" class="manage-column column-title" style="width:95%;" scope="col"><?php _e('Block Name', SAM_DOMAIN);?></th>
      </tr>
    </tfoot>
    <tbody>
      <?php
      $bSql = "SELECT 
                  $bTable.id, 
                  $bTable.name, 
                  $bTable.description,
                  $bTable.trash 
                FROM $bTable".
                (($mode !== 'all') ? " WHERE $bTable.trash = ".(($mode === 'trash') ? 'TRUE' : 'FALSE') : '').
                " LIMIT $offset, $places_per_page";
      $blocks = $wpdb->get_results($bSql, ARRAY_A);          
      $i = 0;
      if(!is_array($blocks) || empty ($blocks)) {
      ?>
      <tr class="no-items">
        <th class="colspanchange" colspan='2'><?php _e('There are no data ...', SAM_DOMAIN); ?></th>
      </tr>
        <?php } else {
          foreach($blocks as $row) {            
        ?>
      <tr id="<?php echo $row['id'];?>" class="<?php echo (($i & 1) ? 'alternate' : ''); ?> author-self status-publish iedit" valign="top">
        <th class="post-title column-title"><?php echo $row['id']; ?></th>
        <td class="post-title column-title">
          <strong style='display: inline;'><a href="<?php echo admin_url('admin.php'); ?>?page=sam-block-edit&action=edit&mode=block&item=<?php echo $row['id']; ?>"><?php echo $row['name'];?></a><?php echo ((($row['trash'] == true) && ($mode === 'all')) ? '<span class="post-state"> - '.__('in Trash', SAM_DOMAIN).'</span>' : ''); ?></strong><br/><?php echo $row['description'];?>
          <div class="row-actions">
            <span class="edit"><a href="<?php echo admin_url('admin.php'); ?>?page=sam-block-edit&action=edit&mode=block&item=<?php echo $row['id']; ?>" title="<?php _e('Edit Block', SAM_DOMAIN) ?>"><?php _e('Edit', SAM_DOMAIN); ?></a> | </span>
            <?php 
            if($row['trash'] == true) { 
              ?>
              <span class="untrash"><a href="<?php echo admin_url('admin.php'); ?>?page=sam-block-list&action=blocks&mode=<?php echo $mode ?>&iaction=untrash&item=<?php echo $row['id'] ?>" title="<?php _e('Restore this Block from the Trash', SAM_DOMAIN) ?>"><?php _e('Restore', SAM_DOMAIN); ?></a> | </span>
              <span class="delete"><a href="<?php echo admin_url('admin.php'); ?>?page=sam-block-list&action=blocks&mode=<?php echo $mode ?>&iaction=kill&item=<?php echo $row['id'] ?>" title="<?php _e('Remove this Block permanently', SAM_DOMAIN) ?>"><?php _e('Remove permanently', SAM_DOMAIN); ?></a></span>
            <?php 
            } 
            else { 
              ?>
              <span class="delete"><a href="<?php echo admin_url('admin.php'); ?>?page=sam-block-list&action=blocks&mode=<?php echo $mode ?>&iaction=delete&item=<?php echo $row['id'] ?>" title="<?php _e('Move this Block to the Trash', SAM_DOMAIN) ?>"><?php _e('Delete', SAM_DOMAIN); ?></a></span>
            <?php } ?>
          </div>
        </td>
      </tr>
        <?php $i++; }}?>
    </tbody>
  </table>
  <div class="tablenav">
    <div class="alignleft">
      <?php if($mode === 'trash') {?>
      <a class="button-secondary" href="<?php echo admin_url('admin.php'); ?>?page=sam-block-list&action=blocks&mode=trash&iaction=kill-em-all"><?php _e('Clear Trash', SAM_DOMAIN); ?></a>
      <?php } else { ?>
      <a class="button-secondary" href="<?php echo admin_url('admin.php'); ?>?page=sam-block-edit&action=new&mode=block"><?php _e('Add New Block', SAM_DOMAIN); ?></a>      
      <?php } ?>
    </div>
    <div class="tablenav-pages">
      <?php $page_links_text = sprintf( '<span class="displaying-num">' . __( 'Displaying %s&#8211;%s of %s', SAM_DOMAIN ) . '</span>%s',
        number_format_i18n( $start + 1 ),
        number_format_i18n( min( $apage * $places_per_page, $total ) ),
        '<span class="total-type-count">' . number_format_i18n( $total ) . '</span>',
        $page_links
      ); echo $page_links_text; ?>
    </div>
  </div>
</div>      
      <?php
    }
  }
}
?>
