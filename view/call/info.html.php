<?php

use Goteo\Library\Text,
    Goteo\Core\View;

$bodyClass = 'info';

$call = $this['call'];

include 'view/call/prologue.html.php';
include 'view/call/header.html.php';
?>
<script type="text/javascript">
$(document).ready(function() {
   $("div.click").click(function() {
       $(this).children("blockquote").toggle();
       $(this).children("span.icon").toggleClass("closed");
    });
 });
</script>

	<div id="main">

		<?php echo new View('view/call/side.html.php', $this); ?>
	
		<div id="content">
			<h2 class="title"><?php echo Text::get('call-splash-campaign_title') ?><br /><?php echo $call->name ?></h2>
            <?php if ($call->status == 3) : //inscripcion ?>
            <p class="subtitle red"><?php echo Text::get('call-splash-searching_projects') ?></p>
            <?php else : //en campaña ?>
            <p class="subtitle"><?php echo Text::get('call-splash-invest_explain', $call->user->name) ?></p>
            <?php endif; ?>
			<div class="freetext">

				<h2 class="title">Informaci&oacute;n general de la campa&ntilde;a</h2>

				<p><?php echo $call->description ?></p>

                <span style="font-size:15px;font-weight:bold;">¿Quiénes pueden participan?</span>
				<p><?php echo $call->whom ?></p>

            <?php if ($call->status == 3) : //inscripcion ?>
                <span style="font-size:15px;font-weight:bold;">¿Cómo puedo publicar un proyecto?</span>
				<p><?php echo $call->apply ?></p>
            <?php else : //en campaña ?>
                
                    <h2 class="title"><?php echo Text::get('call-splash-selected_projects-header') ?></h2>

				<table class="info-table" width="100%">
					<thead class="task">
						<tr>
							<th class="summary">Aportaciones:</th>
							<th class="min">Campaña</th>
							<th class="max">Usuarios</th>
						</tr>
					</thead>
					<tbody>
                        <?php
                        $tot_min = 0;
                        $tot_max = 0;
                        $odd = true;
                        foreach ($call->projects as $proj) :

                            $tot_min += 2000;
                            $tot_min += 3000;
                        ?>
						<tr class="<?php if ($odd) {echo 'odd'; $odd = false;} else {echo 'even'; $odd = true;} ?>">
							<th class="summary">
								<div class="click">
									<span class="icon closed">&nbsp;</span> <span><strong><?php echo $proj->name ?></strong></span>
									<blockquote style="display: none;"><?php echo $proj->subtitle ?></blockquote>
								</div>
							</th>
							<td class="min">2000 &euro;</td>
							<td class="max">3000 &euro;</td>
						</tr>
                        <?php endforeach; ?>
					</tbody>
					<tfoot>
						<tr>
							<th class="total"><?php echo Text::get('regular-total'); ?></th>
							<th class="min"><?php echo $tot_min ?> &euro;</th>
							<th class="max"><?php echo $tot_max ?> &euro;</th>
						</tr>
					</tfoot>
				</table>
            <?php endif; ?>
                
			</div>

       <?php if ($call->status == 3) : //inscripcion ?>
            <p class="block">
                <a class="aqua" href="<?php echo SITE_URL ?>/call/<?php echo $call->id ?>/terms"><?php echo Text::get('call-splash-legal-link') ?></a>
            </p>

            <?php if (!$call->expired) : // sigue abierta ?>
            <a href="<?php echo SITE_URL ?>/call/<?php echo $call->id ?>/apply" class="button red join" target="_blank"><?php echo Text::get('call-splash-apply-button') ?></a>
            <?php endif; ?>

        <?php else : //en campaña ?>
            <a href="<?php echo SITE_URL ?>/call/<?php echo $call->id ?>/projects" class="button red view"><?php echo Text::get('call-splash-see_projects-button') ?></a>
        <?php endif; ?>

		</div>	
	
	</div>

<?php 

include 'view/call/footer.html.php';

include 'view/epilogue.html.php';

 ?>