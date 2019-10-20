<?php require_once 'engine/init.php'; include 'layout/overall/header.php';
 
if ($config['log_ip']) {
	znote_visitor_insert_detailed_data(4);
}

if (isset($_GET['name']) === true && empty($_GET['name']) === false) {
	$name = getValue($_GET['name']);
	$user_id = user_character_exist($name);
	
	if ($user_id !== false) {
		$loadOutfits = $config['show_outfits']['characterprofile'];

		if ($config['ServerEngine'] == 'TFS_10') {
			if (!$loadOutfits) {
				$profile_data = user_character_data($user_id, 'account_id', 'name', 'level', 'group_id', 'vocation', 'health', 'healthmax', 'experience', 'mana', 'manamax', 'sex', 'lastlogin');
			} else { // Load outfits
				$profile_data = user_character_data($user_id, 'account_id', 'name', 'level', 'group_id', 'vocation', 'health', 'healthmax', 'experience', 'mana', 'manamax', 'sex', 'lastlogin', 'lookbody', 'lookfeet', 'lookhead', 'looklegs', 'looktype', 'lookaddons');
			}
			$profile_data['online'] = user_is_online_10($user_id);
			
			if ($config['Ach']) {
				$user_id = (int) $user_id;
				$achievementPoints = mysql_select_single("SELECT SUM(`value`) AS `sum` FROM `player_storage` WHERE `key` LIKE '30___' AND `player_id`={$user_id} LIMIT 1");
			}
		} else { // TFS 0.2, 0.3
			if (!$loadOutfits) {
				$profile_data = user_character_data($user_id, 'name', 'account_id', 'level', 'group_id', 'vocation', 'health', 'healthmax', 'experience', 'mana', 'manamax', 'lastlogin', 'online', 'sex');
			} else { // Load outfits
				if ($config['ServerEngine'] !== 'OTHIRE')
					$profile_data = user_character_data($user_id, 'name', 'account_id', 'level', 'group_id', 'vocation', 'health', 'healthmax', 'experience', 'mana', 'manamax', 'lastlogin', 'online', 'sex', 'lookbody', 'lookfeet', 'lookhead', 'looklegs', 'looktype', 'lookaddons');
				else
					$profile_data = user_character_data($user_id, 'name', 'account_id', 'level', 'group_id', 'vocation', 'health', 'healthmax', 'experience', 'mana', 'manamax', 'lastlogin', 'online', 'sex', 'lookbody', 'lookfeet', 'lookhead', 'looklegs', 'looktype');
			}
		}
		
		$profile_znote_data = user_znote_character_data($user_id, 'created', 'hide_char', 'comment');
		$guild_exist = false;
		if (get_character_guild_rank($user_id) > 0) {
			$guild_exist = true;
			$guild = get_player_guild_data($user_id);
			$guild_name = get_guild_name($guild['guild_id']);
		}
		?>

		<!-- PROFILE MARKUP HERE-->
		<table id="characterProfileTable">
			<thead>
				<tr class="yellow">
					<th>
						<?php if ($loadOutfits): ?>
							<div class="outfit">
								<img src="<?php echo $config['show_outfits']['imageServer']; ?>?id=<?php echo $profile_data['looktype']; ?>&addons=<?php echo $profile_data['lookaddons']; ?>&head=<?php echo $profile_data['lookhead']; ?>&body=<?php echo $profile_data['lookbody']; ?>&legs=<?php echo $profile_data['looklegs']; ?>&feet=<?php echo $profile_data['lookfeet']; ?>" alt="img">
							</div>
						<?php endif; 
						$flags = $config['country_flags'];
						if ($flags['enabled'] && $flags['characterprofile']) { 
							$account_data = user_znote_account_data($profile_data['account_id'], 'flag');
							if (strlen($account_data['flag']) > 0):
								?><!-- Player country data -->
								<div class="flag">
									<img src="<?php echo $flags['server'] . '/' . $account_data['flag']; ?>.png">
								</div>
								<?php
							endif;
						}
						?>
					</th>
					<th>
						<h1><?php echo $profile_data['name']; ?></h1>
					</th>
				</tr>
			</thead>
			<tbody>
				<!-- Player Position -->
				<?php if ($profile_data['group_id'] > 1): ?>
					<tr>
						<td>Position</td>
						<td><?php echo group_id_to_name($profile_data['group_id']); ?></td>
					</tr>
				<?php endif; ?>
				<!-- Player male / female -->
				<tr>
					<td>Sex</td>
					<td><?php echo ($profile_data['sex'] == 1) ? 'Male' : 'Female'; ?></td>
				</tr>
				<!-- Player level -->
				<tr>
					<td>Level</td>
					<td><?php echo $profile_data['level']; ?></td>
				</tr>
				<!-- Player vocation -->
				<tr>
					<td>Vocation</td>
					<td><?php echo vocation_id_to_name($profile_data['vocation']); ?></td>
				</tr>
				<!-- Player guild -->
				<?php if ($guild_exist): ?>
					<tr>
						<td>Guild</td>
						<td><b><?php echo $guild['rank_name']; ?> </b> of <a href="guilds.php?name=<?php echo $guild_name; ?>"><?php echo $guild_name; ?></a></td>
					</tr>
				<?php endif; ?>
				<!-- Player last login -->
				<tr>
					<td>Last Login</td>
					<td><?php echo ($profile_data['lastlogin'] != 0) ? getClock($profile_data['lastlogin'], true, true) : 'Never.'; ?></td>
				</tr>
				<!-- Achievement start -->
				<?php if ($config['Ach'] && (int)$achievementPoints['sum'] > 0): ?>
					<tr>
						<td>Achievement Points</td>
						<td><?php echo (int)$achievementPoints['sum']; ?></td>
					</tr>
				<?php endif; ?>
				<!-- Display house start -->
				<?php
				if ($config['ServerEngine'] !== 'TFS_02') {
					// Compatibility fix
					$column_town_id = array(
						'OTHIRE' => 'townid',
						'TFS_03' => 'town'
						// Default: town_id
					);
					$column_town_id = (isset($column_town_id[$config['ServerEngine']])) 
					? $column_town_id[$config['ServerEngine']] 
					: 'town_id';

					$houses = mysql_select_multi("
						SELECT `id`, `owner`, `name`, `{$column_town_id}` AS `town_id` 
						FROM `houses` 
						WHERE `owner` = {$user_id};
					");
					
					if ($houses !== false) {
						foreach ($houses as $h): ?>
							<tr>
								<td>House</td>
								<td><?php echo $h['name'] . ', ' . $config['towns'][$h['town_id']]; ?></td>
							</tr>
						<?php endforeach;
					}
				}
				?>
				<!-- Display player status -->
				<tr class="status_<?php echo ($profile_data['online']) ? 'online' : 'offline'; ?>">
					<td>Status</td>
					<td><?php echo ($profile_data['online']) ? 'online' : 'offline'; ?></td>
				</tr>
				<!-- Player created -->
				<tr>
					<td>Created</td>
					<td><?php echo getClock($profile_znote_data['created'], true); ?></td>
				</tr>
			</tbody>
		</table>

		<!-- Player Comment -->
		<?php if (!empty($profile_znote_data['comment'])): ?>
			<table class="comment">
				<thead>
					<tr class="yellow">
						<td><font class="profile_font" name="profile_font_comment">Comment:</font></td>
					</tr>
				</thead>
				<tbody>
					<tr>
						<td><?php echo preg_replace('/\v+|\\\r\\\n/','<br/>',$profile_znote_data['comment']); ?></td>
					</tr>
				</tbody>
			</table>
		<?php endif; ?>

<?php
/*/
/   Player character profile EQ shower
/   Based on code from CorneX
/   Written to Znote AAC by Znote.
/   Should work on all TFS versions.
/   Znote AAC 1.4+
/*/

// Item image server
$imageServer = $config['shop']['imageServer'];
$imageType = $config['shop']['imageType'];
if (count($imageType) < 3) $imageType = 'gif';
// Fetch player equipped data
$PEQD = mysql_select_multi("SELECT `player_id`, `pid`, `itemtype`, `count` FROM `player_items` WHERE `player_id`='$user_id' AND `pid`<'11'");
// If player have equipped items
if ($PEQD !== false) {
    // PEQD = Player EQ Data
    $PEQ = array(
        1 => false,
        2 => false,
        3 => false,
        4 => false,
        5 => false,
        6 => false,
        7 => false,
        8 => false,
        9 => false,
        10 => false,
    );
    // Fill player equipments array with fetched data results (PEQD)
    foreach ($PEQD as $EQ) $PEQ[$EQ['pid']] = "http://{$imageServer}/".$EQ['itemtype'].".{$imageType}";
    ?>
    <!-- Fix item positions CSS -->
    <style type="text/css">
    /* CSS by CorneX */
    .signBgrnd {
        background-image:url('eq/outfit.png');
        background-repeat:no-repeat;
        position:relative;
        float: right;
        margin:10px;
        padding:10px;
        height:153px;
        width:118px;
    }
    /* Weapon */
    .signBgrnd .wep {
        position: absolute;
        top: 56px;
        left: 6px;
        width: 32px;
        height: 32px;
    }
    .signBgrnd .wep img {
        background-image:url('eq/bg.png');
        max-width: 100%;
    }
    /* Armor */
    .signBgrnd .arm {
        position: absolute;
        top: 41px;
        left: 43px;
        width: 32px;
        height: 32px;
    }
    .signBgrnd .arm img {
        background-image:url('eq/bg.png');
        max-width: 100%;
    }
    /* Helmet */
    .signBgrnd .helm {
        position: absolute;
        top: 5px;
        left: 43px;
        width: 32px;
        height: 32px;
    }
    .signBgrnd .helm img {
        background-image:url('eq/bg.png');
        max-width: 100%;
    }
    /* legs */
    .signBgrnd .legs {
        position: absolute;
        top: 79px;
        left: 43px;
        width: 32px;
        height: 32px;
    }
    .signBgrnd .legs img {
        background-image:url('eq/bg.png');
        max-width: 100%;
    }
    /* boots */
    .signBgrnd .boots {
        position: absolute;
        top: 116px;
        left: 43px;
        width: 32px;
        height: 32px;
    }
    .signBgrnd .boots img {
        background-image:url('eq/bg.png');
        max-width: 100%;
    }
    /* ring */
    .signBgrnd .ring {
        position: absolute;
        top: 93px;
        left: 6px;
        width: 32px;
        height: 32px;
    }
    .signBgrnd .ring img {
        background-image:url('eq/bg.png');
        max-width: 100%;
    }
    /* amulet */
    .signBgrnd .amulet {
        position: absolute;
        top: 20px;
        left: 7px;
        width: 32px;
        height: 32px;
    }
    .signBgrnd .amulet img {
        background-image:url('eq/bg.png');
        max-width: 100%;
    }
    /* backpack */
    .signBgrnd .backpack {
        position: absolute;
        top: 20px;
        left: 80px;
        width: 32px;
        height: 32px;
    }
    .signBgrnd .backpack img {
        background-image:url('eq/bg.png');
        max-width: 100%;
    }
    /* shield */
    .signBgrnd .shield {
        position: absolute;
        top: 56px;
        left: 80px;
        width: 32px;
        height: 32px;
    }
    .signBgrnd .shield img {
        background-image:url('eq/bg.png');
        max-width: 100%;
    }
    /* arrow */
    .signBgrnd .arrow {
       position: absolute;
       top: 93px;
       left: 80px;
       width: 32px;
       height: 32px;
    }
    .signBgrnd .arrow img {
        background-image:url('eq/bg.png');
        max-width: 100%;
    }
    </style>
    <!-- Render HTML -->
    <div class="signBgrnd">
        <div class="helm">
            <?php
            if ($PEQ[1] != false) {
                ?>
                <img src="<?php echo $PEQ[1]; ?>" alt="Image of player helmet">
                <?php
            }
            ?>
        </div>
        <div class="amulet">
            <?php
            if ($PEQ[2] != false) {
                ?>
                <img src="<?php echo $PEQ[2]; ?>" alt="Image of player amulet">
                <?php
            }
            ?>
        </div>
        <div class="wep">
            <?php
            if ($PEQ[6] != false) {
                ?>
                <img src="<?php echo $PEQ[6]; ?>" alt="Image of player left hand">
                <?php
            }
            ?>
        </div>
        <div class="ring">
            <?php
            if ($PEQ[9] != false) {
                ?>
                <img src="<?php echo $PEQ[9]; ?>" alt="Image of player ring">
                <?php
            }
            ?>
        </div>
        <div class="arm">
            <?php
            if ($PEQ[4] != false) {
                ?>
                <img src="<?php echo $PEQ[4]; ?>" alt="Image of player armor">
                <?php
            }
            ?>
        </div>
        <div class="legs">
            <?php
            if ($PEQ[7] != false) {
                ?>
                <img src="<?php echo $PEQ[7]; ?>" alt="Image of player legs">
                <?php
            }
            ?>
        </div>
        <div class="boots">
            <?php
            if ($PEQ[8] != false) {
                ?>
                <img src="<?php echo $PEQ[8]; ?>" alt="Image of player boots">
                <?php
            }
            ?>
        </div>
        <div class="backpack">
            <?php
            if ($PEQ[3] != false) {
                ?>
                <img src="<?php echo $PEQ[3]; ?>" alt="Image of player backpack">
                <?php
            }
            ?>
        </div>
        <div class="shield">
            <?php
            if ($PEQ[5] != false) {
                ?>
                <img src="<?php echo $PEQ[5]; ?>" alt="Image of player shield">
                <?php
            }
            ?>
        </div>
        <div class="arrow">
            <?php
            if ($PEQ[10] != false) {
                ?>
                <img src="<?php echo $PEQ[10]; ?>" alt="Image of player arrow">
                <?php
            }
            ?>
        </div>
    </div>
    <?php
}
?>    <!-- END EQ SHOWER -->

<?php
/*/
/   Znote AAC 1.4+ detailed character info (HP, MP, lvL, Exp, skills)
/   HTML code based on code from Gesior
/*/
$tableWidth = 540;
if ($config['TFSVersion'] != 'TFS_10') {
    $playerData = mysql_select_multi("SELECT `value` FROM `player_skills` WHERE `player_id`='$user_id' LIMIT 7;");
    $playerData['fist'] = $playerData[0]['value']; unset($playerData[0]);
    $playerData['club'] = $playerData[1]['value']; unset($playerData[1]);
    $playerData['sword'] = $playerData[2]['value']; unset($playerData[2]);
    $playerData['axe'] = $playerData[3]['value']; unset($playerData[3]);
    $playerData['dist'] = $playerData[4]['value']; unset($playerData[4]);
    $playerData['shield'] = $playerData[5]['value']; unset($playerData[5]);
    $playerData['fish'] = $playerData[6]['value']; unset($playerData[6]);

    $player = mysql_select_single("SELECT `health`, `healthmax`, `mana`, `manamax`, `experience`, `maglevel`, `level` FROM `players` WHERE `id`='$user_id' LIMIT 1;");
    $playerData['magic'] = $player['maglevel'];
    $playerData['exp'] = array(
        'now' => $player['experience'],
        'next' => (int)(level_to_experience($player['level']+1) - $player['experience']),
        'percent' => (int)(($player['experience'] - level_to_experience($player['level'])) / (level_to_experience($player['level']+1) - $player['experience']) * 100)
    );
    $playerData['health'] = array(
        'now' => $player['health'],
        'max' => $player['healthmax'],
        'percent' => (int)($player['health'] / $player['healthmax'] * 100),
    );
    $playerData['mana'] = array(
        'now' => $player['mana'],
        'max' => $player['manamax'],
        'percent' => (int)($player['mana'] / $player['manamax'] * 100),
    );
} else {
    $player = mysql_select_single("SELECT `health`, `healthmax`, `mana`, `manamax`, `experience`, `skill_fist`, `skill_club`, `skill_sword`, `skill_axe`, `skill_dist`, `skill_shielding`, `skill_fishing`, `maglevel`, `level` FROM `players` WHERE `id`='$user_id' LIMIT 1;");
    $playerData = array(
        'fist' => $player['skill_fist'],
        'club' => $player['skill_club'],
        'sword' => $player['skill_sword'],
        'axe' => $player['skill_axe'],
        'dist' => $player['skill_dist'],
        'shield' => $player['skill_shielding'],
        'fish' => $player['skill_fishing'],
        'magic' => $player['maglevel'],
        'exp' => array(
            'now' => $player['experience'],
            'next' => (int)(level_to_experience($player['level']+1) - $player['experience']),
            'percent' => (int)(($player['experience'] - level_to_experience($player['level'])) / (level_to_experience($player['level']+1) - $player['experience']) * 100)
        ),
        'health' => array(
            'now' => $player['health'],
            'max' => $player['healthmax'],
            'percent' => (int)($player['health'] / $player['healthmax'] * 100),
        ),
        'mana' => array(
            'now' => $player['mana'],
            'max' => $player['manamax'],
            'percent' => (int)($player['mana'] / $player['manamax'] * 100),
        )
    );
}
// Incase they have more health/mana than they should due to equipment bonus etc
if ($playerData['exp']['percent'] > 100) $playerData['exp']['percent'] = 100;
if ($playerData['health']['percent'] > 100) $playerData['health']['percent'] = 100;
if ($playerData['mana']['percent'] > 100) $playerData['mana']['percent'] = 100;
//data_dump($playerData, false, "Player Data");
?>
<table cellspacing="1" cellpadding="4" style="width: <?php echo $tableWidth; ?>px;">
    <tr>
        <td bgcolor="#1f1f1f" align="left" width="20%">
            <b>Player HP:</b>
        </td>
        <td bgcolor="#1f1f1f" align="left">
            <?php echo $playerData['health']['now'].'/'.$playerData['health']['max']; ?>
            <div style="width: 100%; height: 3px; border: 1px solid #000;">
                <div style="background: red; width: <?php echo $playerData['health']['percent']; ?>%; height: 3px;">
                </div>
            </div>
        </td>
    </tr>
    <tr>
        <td bgcolor="#1f1f1f" align="left">
            <b>Player MP:</b>
        </td>
        <td bgcolor="#1f1f1f" align="left">
            <?php echo $playerData['mana']['now'].'/'.$playerData['mana']['max']; ?>
            <div style="width: 100%; height: 3px; border: 1px solid #000;">
                <div style="background: blue; width: <?php echo $playerData['mana']['percent']; ?>%; height: 3px;">
                </div>
            </div>
        </td>
    </tr>
    <tr>
        <td bgcolor="#1f1f1f" align="left">
            <b>Player XP:</b>
        </td>
        <td bgcolor="#1f1f1f" align="left">
            <?php echo number_format($playerData['exp']['now'], 0, "", " "); ?> Experience.
        </td>
    </tr>
    <tr>
        <td bgcolor="#1f1f1f" align="left">
            <b>To Next Lvl:</b>
        </td>
        <td bgcolor="#1f1f1f" align="left">
            Need <b><?php echo number_format($playerData['exp']['next'], 0, "", " "); ?> experience (<?php echo 100-$playerData['exp']['percent']; ?>%)</b> to Level <b><?php echo $player['level']+1; ?></b>.
            <div title="99.320604545 %" style="width: 100%; height: 3px; border: 1px solid #000;">
                <div style="background: red; width: <?php echo $playerData['exp']['percent']; ?>%; height: 3px;"></div>
            </div>
        </td>
    </tr>
</table>

<!-- PLAYER SKILLS TABLE -->
<table cellspacing="1" cellpadding="4" style="width: <?php echo $tableWidth; ?>px;">
    <tr class="yellow">
        <th>Magic</th>
        <th>Club</th>
        <th>Sword</th>
        <th>Axe</th>
        <th>Dist</th>
        <th>Shield</th>
        <th>Fish</th>
    </tr>
    <tr>
        <td bgcolor="#1f1f1f" align="left">
            <?php echo $playerData['magic']; ?>
        </td>
        <td bgcolor="#1f1f1f" align="left">
            <?php echo $playerData['club']; ?>
        </td>
        <td bgcolor="#1f1f1f" align="left">
            <?php echo $playerData['sword']; ?>
        </td>
        <td bgcolor="#1f1f1f" align="left">
            <?php echo $playerData['axe']; ?>
        </td>
        <td bgcolor="#1f1f1f" align="left">
            <?php echo $playerData['dist']; ?>
        </td>
        <td bgcolor="#1f1f1f" align="left">
            <?php echo $playerData['shield']; ?>
        </td>
        <td bgcolor="#1f1f1f" align="left">
            <?php echo $playerData['fish']; ?>
        </td>
    </tr>
</table>
<!-- PLAYER INFO TABLE -->

<!-- Quest information by Krotus to Otland.net-->
				<div>
				<table>
					<th colspan="2">Quests</th> 
				<?php
				$storage=array('Blue Legs Quest' => 5124, 'Greenhorn Arena' => 42361, 'Scrapper Arena' => 42371, 'Warlord Arena' => 42381, 'Behemoth Quest' => 60058, 'Annihilator' => 49111, 'Annihilator V2' => 8005, 'Dwarven Armor Quest' => 5053, 'Dwarven Legs Quest' => 5080, 'Helmet Of The Ancient' => 5125, 'Demon Helmet Quest' => 60746, 'Pits Of Inferno' => 10544, 'The Inquisition' => 8560, 'The Demon Oak' => 7201, 'Yalahar Quest' => 102504, 'Firewalker Boots Quest' => 102504, 'Demonic Svargrond Arena' => 585783, 'Arcane Amulet Quest' => 42391);
				foreach($storage as $name => $value)
				{
					echo "<tr>";
					echo "<td width=100%>".$name."</td>";
					$quest = mysql_select_single("SELECT `value` FROM `player_storage` WHERE `key`='$value' AND `player_id`='$user_id' LIMIT 1;"); 
					if ($quest['value'] == 1) {
						echo "<td><img src='images/yes_quest.png'></td>";
					}else{
						echo "<td><img src='images/no_quest.png' ></td>";
					}
					echo "</tr>";
				}
				?>
				</table>
				</div>

<!-- END detailed character info -->

		<!-- Achievements start -->
		<?php if ($config['Ach']): 
			$achievements = mysql_select_multi("
				SELECT `player_id`, `value`, `key` 
				FROM `player_storage` 
				WHERE `player_id`='$user_id' 
				AND `key` LIKE '30___';
			");
			$c_achs = $config['achievements'];
			$toggle = array(
				'show' => '<a href="#show">Show</a>',
				'hide' => '<a href="#hide">Hide</a>'
			);
			if ($achievements !== false): ?>
				<h3>Achievements: <label id="ac_label_hide" for="ac_toggle_hide"><?php echo $toggle['show']; ?></label></h3>
				<!-- <div id="accordion">
					<h3>Show/hide player achievements</h3>
					<div>
						
					</div>
				</div><br> -->
				<input type="checkbox" id="ac_toggle_hide" name="ac_toggle_hide">
				<table class="achievements">
					<thead>
						<tr>
							<th>Name</th>
							<th>Description</th>
							<th>Points</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach($achievements as $a): ?>
							<tr>
								<td><?php echo $c_achs[$a['key']][0]; ?></td>
								<td><?php echo $c_achs[$a['key']][1]; ?></td>
								<td><?php echo $a['value']; ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<style type="text/css">
					table.achievements,
					#ac_toggle_hide {
						display: none;
					}
					#ac_toggle_hide:checked + table.achievements {
						display: table;
					}
				</style>
				<script type="text/javascript">
					document.getElementById("ac_label_hide").addEventListener("click", function(event){
						event.preventDefault();
						if (document.getElementById("ac_label_hide").innerHTML == "<?php echo str_replace('"', '\"', $toggle['show']); ?>") {
							document.getElementById("ac_label_hide").innerHTML = "<?php echo str_replace('"', '\"', $toggle['hide']); ?>";
							document.getElementById("ac_toggle_hide").checked = true;
						} else {
							document.getElementById("ac_label_hide").innerHTML = "<?php echo str_replace('"', '\"', $toggle['show']); ?>";
							document.getElementById("ac_toggle_hide").checked = false;
						}
					});
				</script>
			<?php endif; ?>
		<?php endif; ?>
		
		<!-- DEATH LIST -->
		<table class="deathlist">
			<thead>
				<tr class="yellow">
					<th colspan="2">Death List</th>
				</tr>
			</thead>
			<tbody>
				<?php
				if ($config['ServerEngine'] == 'TFS_10') {
					$deaths = mysql_select_multi("
						SELECT 
							`player_id`, 
							`time`, 
							`level`, 
							`killed_by`, 
							`is_player`, 
							`mostdamage_by`, 
							`mostdamage_is_player`, 
							`unjustified`, 
							`mostdamage_unjustified` 
						FROM `player_deaths` 
						WHERE `player_id`=$user_id 
						ORDER BY `time` DESC 
						LIMIT 10;
					");

					if ($deaths) {
						foreach ($deaths as $d) {
							$lasthit = ($d['is_player']) 
							? "<a href='characterprofile.php?name=".$d['killed_by']."'>".$d['killed_by']."</a>" 
							: $d['killed_by'];

							?>
							<tr>
								<td><?php echo getClock($d['time'], true, true); ?></td>
								<td>
									<?php 
									echo "Killed at level ".$d['level']." by {$lasthit}"; 
									if ($d['unjustified']) {
										echo " <font color='red' style='font-style: italic;'>(unjustified)</font>";
									}
									$mostdmg = ($d['mostdamage_by'] !== $d['killed_by']) ? true : false;
									if ($mostdmg) {
										$mostdmg = ($d['mostdamage_is_player']) 
										? "<a href='characterprofile.php?name=".$d['mostdamage_by']."'>".$d['mostdamage_by']."</a>" 
										: $d['mostdamage_by'];
										
										echo "<br>and by $mostdmg.";
										
										if ($d['mostdamage_unjustified']) { 
											echo " <font color='red' style='font-style: italic;'>(unjustified)</font>"; 
										}
									} else { 
										echo " <b>(soloed)</b>"; 
									}
									?>
								</td>
							</tr>
							<?php
						}
					} else {
						?>
						<tr>
							<td colspan="2">This player has never died.</td>
						</tr>
						<?php
					}
				} elseif ($config['ServerEngine'] == 'TFS_02') {
					$array = user_fetch_deathlist($user_id);
					if ($array) {
						foreach ($array as $value): 
							if ($value['is_player'] == 1) {
								$value['killed_by'] = 'player: <a href="characterprofile.php?name='. $value['killed_by'] .'">'. $value['killed_by'] .'</a>';
							} else {
								$value['killed_by'] = 'monster: '. $value['killed_by'] .'.';
							}
							?>
							<tr>
								<td><?php echo getClock($value['time'], true, true); ?></td>
								<td><?php echo 'Killed at level '. $value['level'] .' by '. $value['killed_by']; ?></td>
							</tr>
						<?php endforeach;
					} else {
						?>
						<tr>
							<td colspan="2">This player has never died.</td>
						</tr>
						<?php
					}
				} elseif (in_array($config['ServerEngine'], array('TFS_03', 'OTHIRE'))) {
					//mysql_select_single("SELECT * FROM players WHERE name='TEST DEBUG';");
					$array = user_fetch_deathlist03($user_id);
					if ($array) {
						// Design and present the list
						foreach ($array as $value):
							$value[3] = user_get_killer_id(user_get_kid($value['id']));
							if ($value[3] !== false && $value[3] >= 1) {
								$namedata = user_character_data((int)$value[3], 'name');
								if ($namedata !== false) {
									$value[3] = $namedata['name'];
									$value[3] = 'player: <a href="characterprofile.php?name='. $value[3] .'">'. $value[3] .'</a>';
								} else {
									$value[3] = 'deleted player.';
								}
							} else {
								$value[3] = user_get_killer_m_name(user_get_kid($value['id']));
								if ($value[3] === false) { 
									$value[3] = 'deleted player.'; 
								}
							}
							?>
							<tr>
								<td><?php echo getClock($value['date'], true, true); ?></td>
								<td><?php echo 'Killed at level '. $value['level'] .' by '. $value[3]; ?></td>
							</tr>
						<?php endforeach;
					} else { 
						?>
						<tr>
							<td colspan="2">This player has never died.</td>
						</tr>
						<?php
					}
				}
				?>
			</tbody>
		</table>
		
		<!-- QUEST PROGRESSION -->
		<?php
		$totalquests = 0;
		$completedquests = 0;
		$firstrun = 1;
		
		if ($config['EnableQuests'] == true) {
			$sqlquests =  mysql_select_multi("
				SELECT `player_id`, `key`, `value` 
				FROM player_storage 
				WHERE `player_id` = {$user_id}
			");
			if (isset($config['quests']) && !empty($config['quests'])) {
				foreach ($config['quests'] as $cquest) {
					$totalquests = $totalquests + 1;
					foreach ($sqlquests as $dbquest) {
						if ($cquest[0] == $dbquest['key'] && $cquest[1] == $dbquest['value']) {
							$completedquests = $completedquests + 1;
						}
					}
					if ($cquest[3] == 1) {
						if ($completedquests != 0) {
							if ($firstrun == 1): ?>
								<b> Quest progression </b>
								<table id="characterprofileQuest" class="table table-striped table-hover">
									<thead>
										<tr class="yellow">
											<th>Quest:</th>
											<th>progression:</th>
										</tr>
									</thead>
									<tbody>
								<?php
								$firstrun = 0;
							endif;
							$completed = $completedquests / $totalquests * 100;
							?>
							<tr>
								<td><?php echo $cquest[2]; ?></td>
								<td id="progress">
									<span id="percent"><?php echo round($completed); ?>%</span>
									<div id="bar" style="width: '.$completed.'%"></div>
								</td>
							</tr>
							<?php
						}
						$completedquests = 0;
						$totalquests = 0;
					}
				}
			}
		}
		
		if ($firstrun == 0): ?>
			</tbody></table>
		<?php endif; ?>
		<!-- END QUEST PROGRESSION -->

		<!-- CHARACTER LIST -->
		<?php
		if (user_character_hide($profile_data['name']) != 1 && user_character_list_count(user_character_account_id($name)) > 1) 
		{
		?>
			<li>
				<b>Other visible characters on this account:</b><br>
				<?php
				$characters = user_character_list(user_character_account_id($profile_data['name']));
				// characters: [0] = name, [1] = level, [2] = vocation, [3] = town_id, [4] = lastlogin, [5] = online
				if ($characters && count($characters) > 0) {
					?>
					<table id="characterprofileTable" class="table table-striped table-hover">
						<tr class="yellow">
							<th>Name:</th>
							<th>Level:</th>
							<th>Vocation:</th>
							<th>Last login:</th>
							<th>Status:</th>
						</tr>
						
						<?php
						// Design and present the list
						foreach ($characters as $char) {
							if ($char['name'] != $profile_data['name']) {
								if (hide_char_to_name(user_character_hide($char['name'])) != 'hidden'): ?>
									<tr>
										<td><a href="characterprofile.php?name=<?php echo $char['name']; ?>"><?php echo $char['name']; ?></a></td>
										<td><?php echo (int)$char['level']; ?></td>
										<td><?php echo $char['vocation']; ?></td>
										<td><?php echo $char['lastlogin']; ?></td>
										<td><?php echo $char['online']; ?></td>
									</tr>
								<?php endif;
							}
						}
					?>
					</table>
					<?php
				}/* else {
					echo '<b><font color="green">This player has never died.</font></b>';
				}*/
				?>
			</li>
		<?php
		}
		?>
		<!-- END CHARACTER LIST -->
		
		
		<?php
	} else {
		echo htmlentities(strip_tags($name, ENT_QUOTES)) . ' does not exist.';
	}
} else {
	header('Location: index.php');
}
include 'layout/overall/footer.php'; ?>
