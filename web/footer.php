			</div>
			<?php $end = microtime(); ?>
			 <div id="footer">
			 	<div class="container">
			            <p class="muted credit"><a href="http://openstatus.nickmoeck.com">OpenStatus</a> | Page generated in <?php echo round(($end - $start), 5); ?>s.</p>
			 	</div>
			 </div>
			<script type="text/javascript">
			<?php
			echo $lbjs;
			?>
			</script>
		
    </body>
</html>
