<?php 

$name = '';
$site_link = '';

// Ensure helpers.php is loaded for url() function
if (!function_exists('url')) {
    require_once(__DIR__ . '/../../config/helpers.php');
}

if($_SERVER['HTTP_HOST'] == "codedart.cfornesa.com"){
  $name = "CFornesa";
  $name_img = "<img src='https://assets.zyrosite.com/cdn-cgi/image/format=auto,w=300,fit=crop,q=95/mnlqBqP7e2CrkQ0w/cfornesa-logo-m6Ljv5WRPBCQg443.png' alt='CFornesa Logo' width='180px' height='auto'></img>";
  $site_link = "<a href='https://cfornesa.com'>CFornesa</a>";
  $counter = "";
} else if($_SERVER['HTTP_HOST'] == "codedart.fornesus.com"){
  $name = "Fornesus";
  $name_img = "<img src='" . url('img/images/Fornesus%20Logo.png') . "' alt='Fornesus Logo' width='180px' height='auto'></img>";
  $site_link = "<a href='https://fornesus.com'>Fornesus</a>";
  $counter = "";
} else if($_SERVER['HTTP_HOST'] == "codedart.org"){
  $name = "Fornesus";
  $name_img = "<img src='" . url('img/images/Fornesus%20Logo.png') . "' alt='Fornesus Logo' width='180px' height='auto'></img>";
  $site_link = "<a href='https://codedart.org'>Fornesus</a>";
  $counter = "<div class='row visitors'><center><a href='http://www.freevisitorcounters.com'>freevisitorcounters's Website</a> <script type='text/javascript' src='https://www.freevisitorcounters.com/auth.php?id=548bc8bc83f0cba11a42af0930cfa961e7a19cb4'></script>
    <script type='text/javascript' src='https://www.freevisitorcounters.com/en/home/counter/1359127/t/0'></script><p>&nbsp;</p></center></div>";
} else {
  $name = "CFTester";
  $name_img = "<img src='" . url('img/images/Fornesus%20Logo.png') . "' alt='Fornesus Logo' width='180px' height='auto'></img>";
  $site_link = "<a href='https://fornesus.com'>CFTester</a>";
  $counter = "";
}

?>