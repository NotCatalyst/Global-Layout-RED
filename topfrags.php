<?php require_once 'engine/init.php'; include 'layout/overall/header.php';

	// Cache the results
	$cache = new Cache('engine/cache/topMurders');
	if ($cache->hasExpired()) {
		$killers = mysql_select_multi("SELECT `p`.`name` AS `name`, COUNT(`p`.`name`) as `frags` FROM `killers` k LEFT JOIN `player_killers` pk ON `k`.`id` = `pk`.`kill_id` LEFT JOIN `players` p ON `pk`.`player_id` = `p`.`id` WHERE `k`.`unjustified` = 1 AND `k`.`final_hit` = 1 GROUP BY `name` ORDER BY `frags` DESC, `name` ASC LIMIT 0,30;");
		
		$cache->setContent($killers);
		$cache->save();
	} else {
		$killers = $cache->load();
	}

if (!empty($killers) || $killers !== false) {
?>

<table id="onlinelistTable" class="table table-striped table-hover">
	<tr class="yellow">
		<th>Name:</th>
		<th>Frags:</th>
	</tr>
	<?php foreach ($killers as $killer) { ?>
	<tr>
		<td><?php echo $killer['name']; ?></td>
		<td><?php echo $killer['frags']; ?></td>
	</tr>
	<?php } ?>
</table>

<?php
} else echo '<h1>No frags yet.</h1>';
 include 'layout/overall/footer.php'; ?>
