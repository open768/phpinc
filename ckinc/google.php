<?php
//###########################################################################
//#
//###########################################################################
//################################################################################
class cGoogleAnalytics{
	public static function browser_agent($psTagID){
	?>
		<script async src="https://www.googletagmanager.com/gtag/js?id=<?=$psTagID?>"></script>
		<script>
		  window.dataLayer = window.dataLayer || [];
		  function gtag(){dataLayer.push(arguments);}
		  gtag('js', new Date());

		  gtag('config', '<?=$psTagID?>');
		</script>
		<?php
	}
}
?>