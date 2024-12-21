<?php 

$name = '';
$site_link = '';

if($_SERVER['HTTP_HOST'] == "codedart.cfornesa.com"){
  $name = "CFornesa";
  $site_link = "<a href='https://cfornesa.com'>CFornesa</a>";
} else if($_SERVER['HTTP_HOST'] == "codedart.fornesus.com"){
  $name = "Fornesus";
  $site_link = "<a href='https://fornesus.com'>Fornesus</a>";
} else {
  $name = "CFTester";
  $site_link = "<a href='https://fornesus.com'>CFTester</a>";
}

?>