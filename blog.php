<?php 

require('resources/templates/name.php');
require('config/pages.php');

// Get page variables from centralized registry
$pageInfo = getPageInfo();
extract($pageInfo); // Creates $page_name, $tagline, $section, $type

require('resources/templates/head.php');
?>
  <body>
    <?php require("resources/templates/header.php") ?>
    <main class="container">
      <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 centered">
          <h2>Blog</h2>
          <!-- start sw-rss-feed code --> 
          <script type="text/javascript"> 
          <!-- 
          rssfeed_url = new Array(); 
          rssfeed_url[0]="https://fornesus.medium.com/feed"; rssfeed_url[1]="https://fornesus.blog/feed/";  
          rssfeed_frame_width="230"; 
          rssfeed_frame_height="400"; 
          rssfeed_scroll="off"; 
          rssfeed_scroll_step="6"; 
          rssfeed_scroll_bar="on"; 
          rssfeed_target="_blank"; 
          rssfeed_font_size="12"; 
          rssfeed_font_face=""; 
          rssfeed_border="on"; 
          rssfeed_css_url="https://feed.surfing-waves.com/css/style4.css"; 
          rssfeed_title="on"; 
          rssfeed_title_name=""; 
          rssfeed_title_bgcolor="#3366ff"; 
          rssfeed_title_color="#fff"; 
          rssfeed_title_bgimage=""; 
          rssfeed_footer="off"; 
          rssfeed_footer_name="rss feed"; 
          rssfeed_footer_bgcolor="#fff"; 
          rssfeed_footer_color="#333"; 
          rssfeed_footer_bgimage=""; 
          rssfeed_item_title_length="50"; 
          rssfeed_item_title_color="#666"; 
          rssfeed_item_bgcolor="#fff"; 
          rssfeed_item_bgimage=""; 
          rssfeed_item_border_bottom="on"; 
          rssfeed_item_source_icon="off"; 
          rssfeed_item_date="off"; 
          rssfeed_item_description="on"; 
          rssfeed_item_description_length="120"; 
          rssfeed_item_description_color="#666"; 
          rssfeed_item_description_link_color="#333"; 
          rssfeed_item_description_tag="off"; 
          rssfeed_no_items="0"; 
          rssfeed_cache = "2d7009fde237fd5d06fcc4760431e0eb"; 
          //--> 
          </script> 
          <script type="text/javascript" src="//feed.surfing-waves.com/js/rss-feed.js"></script> 
          <!-- The link below helps keep this service FREE, and helps other people find the SW widget. Please be cool and keep it! Thanks. --> 
          <div style="color:#ccc;font-size:10px; text-align:right; width:230px;">powered by <a href="https://surfing-waves.com" rel="noopener" target="_blank" style="color:#ccc;">Surfing Waves</a></div> 
          <!-- end sw-rss-feed code -->
        </div>
      </div>
    </main>
    <?php echo $counter; ?>
    <?php require("resources/templates/footer.php") ?>
  </body>
</html>