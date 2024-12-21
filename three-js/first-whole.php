<?php 

require('../resources/templates/name.php');


  $page_name = "First 3JS";
  $tagline = "Code and code-generated art by " . $site_link . ".";
  require('../resources/templates/head.php');
?>
  <body>
    <!-- Including the three.js library -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/three.js/104/three.min.js"></script>
    <script src="first-whole.js"></script>
    <!--
    This script places a badge on your repl's full-browser view back to your repl's cover  
    page. Try various colors for the theme: dark, light, red, orange, yellow, lime, green,
    teal, blue, blurple, magenta, pink!
    -->
  </body>
</html>