<?php

  require_once 'php/functions.php';
  
  log_visit(__FILE__);

?>
<!DOCTYPE html>
<html lang="<?php echo current_lang(); ?>">
<head>
	<?php echo $google_stats; ?>
    <meta charset="UTF-8"> 
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3wordid.com</title>
    <link rel="icon" type="image/x-icon" href="img/favicon.ico">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	<style>
    main {
      display: flex;
      justify-content: center;
      padding: 20px;
    }
    .search-container {
      max-width: 600px;
      width: 100%;
    }
    </style>
</head>
<body>
    <header>
       <div class="top-right" id="userPortrait">
       <?php echo lang_switcher(); ?>
       <a href="login/options.php" class="login-icon" title="<?php echo t('common.log_in'); ?>">
	   <link rel="stylesheet" href="css/styles_3.css">	   
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-label="<?php echo t('icon.login'); ?>">
            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
            <circle cx="12" cy="7" r="4"></circle>
        </svg>
    </a>
    <img id="userPortrait" style="display:none;" alt="<?php echo t('icon.portrait'); ?>">
</div>
    </header>
   <main>
   <div class="search-container-mid">
    <a href="index.php" class="logo-link">
      <img width="100" src="img/3wid_big.png" alt="<?php echo t('icon.logo_3wid'); ?>">
    </a>
    <h2><?php echo t('reseller.h'); ?></h2>
    <br><br>
	<ul>
	  <li><?php echo t('reseller.l1'); ?></li>
	  <li><?php echo t('reseller.l2'); ?></li>
	  <li><?php echo t_raw('reseller.l3'); ?></li>
	  <li><?php echo t('reseller.l4'); ?></li>
	</ul>
	<br><br>
    <p>
      <?php echo t_raw('reseller.p1'); ?><br><br>
      <?php echo t_raw('reseller.p2'); ?>
    </p>
  </div>
  
</main>
  <?php echo $footer; ?>
</body>
  
</html>

