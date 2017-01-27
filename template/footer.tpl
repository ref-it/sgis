<?php
  global $AUTHGROUP;
?>
<nav class="navbar navbar-default" role="navigation">
  <div class="container-fluid">
    <!-- Brand and toggle get grouped for better mobile display -->
    <div class="navbar-header">
      <ul class="nav navbar-nav navbar-right">
        <li><a href="<?php echo $logoutUrl; ?>">Logout</a></li>
        <li><a href="index.php">Startseite</a></li>
<?php if (hasGroup($AUTHGROUP)): ?>
        <li><a href="admin.php">Verwaltung</a></li>
<?php endif; ?>
<!--        <li><a href="admin-old.php">Alte Verwaltung</a></li> -->
      </ul>
    </div>

  </div><!-- /.container-fluid -->
</nav>

 </div> <!-- container -->
 </body>
</html>
