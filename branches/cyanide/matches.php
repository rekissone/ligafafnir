<?php

/*
 *  Copyright (c) Niels Orsleff Justesen <njustesen@gmail.com> and Nicholas Mossor Rathmann <nicholas.rathmann@gmail.com> 2007-2009. All Rights Reserved.
 *      
 *
 *  This file is part of OBBLM.
 *
 *  OBBLM is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  OBBLM is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *   
 */

function match_form($match_id) {

    // Is $match_id valid?
    if (!get_alt_col('matches', 'match_id', $match_id, 'match_id'))
        fatal("Invalid match ID.");
    
    global $stars;
    global $rules;
    global $lng;
    
    $easyconvert = new array_to_js();
    @$easyconvert->add_array($stars, 'phpStars'); // Load stars array into JavaScript array.
    echo $easyconvert->output_all();

    echo '<script language="JavaScript" type="text/javascript">
    var ID_MERCS = '.ID_MERCS.';
    var ID_STARS_BEGIN = '.ID_STARS_BEGIN.';    
    </script>
    ';
   
    // Create objects
    $coach = (isset($_SESSION['logged_in'])) ? new Coach($_SESSION['coach_id']) : null;
    $m = new Match($match_id);
    $team1 = new Team($m->team1_id);
    $team2 = new Team($m->team2_id);
    
    // Determine visitor privileges.
    $ALLOW_EDIT = false;

    if (!$m->locked && is_object($coach) && ($coach->admin || $coach->isInMatch($m->match_id)))
        $ALLOW_EDIT = true;
    
    $DIS = ($ALLOW_EDIT) ? '' : 'DISABLED';

    /*****************
     *
     * Submitted form?
     *
     *****************/
     
    if (isset($_POST['button']) && $ALLOW_EDIT) {
    
        submit_form($m, $team1, $team2, $coach, $stars, $match_id);
        
        // Refresh objects used to display form.
        $m = new Match($match_id);
        $team1 = new Team($m->team1_id);
        $team2 = new Team($m->team2_id);
    }
    
    // Match comment made?
    if (isset($_POST['msmrc']) && is_object($coach)) {
        status($m->newComment($coach->coach_id, $_POST['msmrc']));
    }
    
    /****************
     *
     * Upload match report ?
     *
     ****************/
    
    if (isset($_POST['upload']) && $_POST['upload']=="true" && $ALLOW_EDIT) {
    	
    	// This moves the match report in the right folder with the right name
    	$rand = mt_rand();
    	$id = "match_report_" .$rand. ".db";
    	uploadMatchReport("uploaded_match_report", UPLOAD_MATCH_REPORT_DIR, $id);
    	
    	$match_report = new CyanideMatchReport(UPLOAD_MATCH_REPORT_DIR ."/". $id);
    	
    	@unlink(UPLOAD_MATCH_REPORT_DIR . "/" . $id);
    	
    	// Check if team1=home team, reverse home and away otherwise
    	if ($team1->name == $match_report->away_team_name)
    		$match_report->reverseHomeAndAway();
    	
    	if ($match_report->home_team_name != $team1->name || $match_report->away_team_name != $team2->name) {
    		
    		title ($lng->getTrn('secs/fixtures/report/wrong_teams'));
    		return;
    	}
    	else {
    		
    		prepareForm($match_report, $m, $team1, $team2);
    	
    		submit_form($m, $team1, $team2, $coach, $stars, $match_id);
    		
	    	// Refresh objects used to display form.
	        $m = new Match($match_id);
	        $team1 = new Team($m->team1_id);
	        $team2 = new Team($m->team2_id);
    	}
    }

    /****************
     *
     * Generate form 
     *
     ****************/

    title((($m->team1_id) ? $m->team1_name : '<i>'.$lng->getTrn('secs/fixtures/undecided').'</i>') . " - " . (($m->team2_id) ? $m->team2_name : '<i>'.$lng->getTrn('secs/fixtures/undecided').'</i>'));
    $CP = 6; // Colspan.

    ?>
    <div>
        <a href='javascript:void(0);' onClick="document.getElementById('helpbox').style.display = 'block';"><?php echo $lng->getTrn('secs/fixtures/report/click');?></a> <?php echo $lng->getTrn('secs/fixtures/report/forhelp');?>
    </div>
    <div id='helpbox' style='display: none;'>
        <br>
        <?php echo $lng->getTrn('secs/fixtures/report/help');?>
    </div>
    <br>
    
    <?php if ($ALLOW_EDIT) { ?>
    <form method="POST" enctype="multipart/form-data" action="index.php?section=fixturelist&amp;match_id=<?php echo $match_id; ?>" >
    	<table class="match_form">
    		<tr>
                <td colspan="<?php echo $CP;?>" class="dark"><b><?php echo $lng->getTrn('secs/cc/main/upload_match_report');?></b></td>
            </tr>
            <tr><td class='seperator' colspan='<?php echo $CP;?>'></td></tr>
            <tr>
                <td colspan='<?php echo $CP;?>'>
                	<input name="uploaded_match_report" type="file"/>
                	<input type="hidden" name="upload" value="true"/>
                	<input type="submit" value="<?php echo $lng->getTrn('secs/cc/main/upload_match_report');?>" />
                </td>
            </tr>
            <tr><td class='seperator' colspan='<?php echo $CP;?>'></td></tr>
        </table>
	</form>
    <?php } ?>
    
    <form method="POST" enctype="multipart/form-data">
        <table class="match_form">
            
            <tr>
                <td colspan="<?php echo $CP;?>" class="dark"><b><?php echo $lng->getTrn('secs/fixtures/report/info');?></b></td>
            </tr>
            <tr><td class='seperator' colspan='<?php echo $CP;?>'></td></tr>
            <tr>
                <td colspan='<?php echo $CP;?>'>
                    <b><?php echo $lng->getTrn('secs/fixtures/report/stad');?></b>&nbsp;
                    <select name="stadium" <?php echo $DIS;?>>
                        <?php
                        $teams = Team::getTeams();
                        objsort($teams, array('+name'));
                        $stad = ($m->stadium) ? $m->stadium : $m->team1_id;
                        foreach ($teams as $_t) {
                            echo "<option value='$_t->team_id' " . (($stad == $_t->team_id) ? 'SELECTED' : '' ) . " ".(($_t->team_id == $m->team1_id || $_t->team_id == $m->team2_id) ? "style='background-color: ".COLOR_HTML_READY.";'" : '').">$_t->name</option>\n";
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td colspan='<?php echo $CP;?>'>
                    <b><?php echo $lng->getTrn('secs/fixtures/report/gate');?></b>&nbsp;
                    <input type="text" name="gate" value="<?php echo $m->gate ? $m->gate/1000 : 0;?>" size="4" maxlength="4" <?php echo $DIS;?>>k
                </td>
            </tr>
            <tr>
                <td colspan='<?php echo $CP;?>'>
                    <b><?php echo $lng->getTrn('secs/fixtures/report/fans');?></b>&nbsp;
                    <input type="text" name="fans" value="<?php echo $m->fans;?>" size="7" maxlength="12" <?php echo $DIS;?>>
                </td>
            </tr>
            
            <tr><td class="seperator" colspan='<?php echo $CP;?>'></td></tr>

            <tr>
                <td class="dark"><b><?php echo $lng->getTrn('secs/fixtures/report/teams');?></b></td>
                <td class="dark"><b><?php echo $lng->getTrn('secs/fixtures/report/score');?></b></td>
                <td class="dark"><b>&Delta; <?php echo $lng->getTrn('secs/fixtures/report/treas');?></b></td>
                <td class="dark"><b><?php echo $lng->getTrn('secs/fixtures/report/ff');?></b></td>
                <td class="dark"><b><?php echo $lng->getTrn('secs/fixtures/report/smp');?></b></td>
                <td class="dark"><b><?php echo $lng->getTrn('secs/fixtures/report/tcas');?></b></td>
            </tr>
            
            <tr><td class='seperator' colspan='<?php echo $CP;?>'></td></tr>

            <tr>
                <td><?php echo $m->team1_name;?></td>
                <td>
                    <input type="text" name="result1" value="<?php echo $m->team1_score ? $m->team1_score : 0;?>" size="1" maxlength="2" <?php echo $DIS;?>>
                </td>
                <td>
                    <input type='text' name='inc_1' value="<?php echo ((int) $m->income1)/1000;?>" size='4' maxlength='4' <?php echo $DIS;?>>k
                </td>
                <td>
                    <input <?php echo $DIS;?> type='radio' name='ff_1' value='1'  <?php echo ($m->ffactor1 == 1)  ? 'CHECKED' : '';?>><font color='green'><b>+1</b></font>
                    <input <?php echo $DIS;?> type='radio' name='ff_1' value='0'  <?php echo ($m->ffactor1 == 0)  ? 'CHECKED' : '';?>><font color='blue'><b>+0</b></font>
                    <input <?php echo $DIS;?> type='radio' name='ff_1' value='-1' <?php echo ($m->ffactor1 == -1) ? 'CHECKED' : '';?>><font color='red'><b>-1</b></font>
                </td>
                <td>
                    <input type="text" name="smp1" value="<?php echo $m->smp1;?>" size="1" maxlength="2" <?php echo $DIS;?>> <?php echo $lng->getTrn('secs/fixtures/report/pts');?>
                </td>
                <td>
                    <input type="text" name="tcas1" value="<?php echo $m->tcas1;?>" size="1" maxlength="2" <?php echo $DIS;?>>
                </td>
            </tr>
            <tr>
                <td><?php echo $m->team2_name;?></td>
                <td>
                    <input type="text" name="result2" value="<?php echo $m->team2_score ? $m->team2_score : 0;?>" size="1" maxlength="2" <?php echo $DIS;?>>
                </td>
                <td>
                    <input type='text' name='inc_2' value="<?php echo ((int) $m->income2)/1000;?>" size='4' maxlength='4' <?php echo $DIS;?>>k
                </td>
                <td>
                    <input <?php echo $DIS;?> type='radio' name='ff_2' value='1'  <?php echo ($m->ffactor2 == 1)  ? 'CHECKED' : '';?>><font color='green'><b>+1</b></font>
                    <input <?php echo $DIS;?> type='radio' name='ff_2' value='0'  <?php echo ($m->ffactor2 == 0)  ? 'CHECKED' : '';?>><font color='blue'><b>+0</b></font>
                    <input <?php echo $DIS;?> type='radio' name='ff_2' value='-1' <?php echo ($m->ffactor2 == -1) ? 'CHECKED' : '';?>><font color='red'><b>-1</b></font>
                </td>
                <td>
                    <input type="text" name="smp2" value="<?php echo $m->smp2;?>" size="1" maxlength="2" <?php echo $DIS;?>> points
                </td>
                <td>
                    <input type="text" name="tcas2" value="<?php echo $m->tcas2;?>" size="1" maxlength="2" <?php echo $DIS;?>>
                </td>
            </tr>
            
        </table>

        <?php
        foreach (array(1 => $team1, 2 => $team2) as $id => $t) {

            ?>
            <table class='match_form'>
            <tr><td class='seperator' colspan='13'></td></tr>
            <tr><td colspan='13' class='dark'>
                <b><?php echo $t->name;?> <?php echo $lng->getTrn('secs/fixtures/report/report');?></b>
            </td></tr>
            <tr><td class='seperator' colspan='13'></td></tr>

            <tr>
                <td><i>Nr</i></td>
                <td><i>Name</i></td>
                <td><i>Position</i></td>
                <td><i>MVP</i></td>
                <td><i>Cp</i></td>
                <td><i>TD</i></td>
                <td><i>Int</i></td>
                <td><i>BH</i></td>
                <td><i>SI</i></td>
                <td><i>Ki</i></td>
                <td><i>Inj</i></td>
                <td><i>Ageing</i></td>
                <td><i>Ageing</i></td>
            </tr>
            <?php
            
            foreach ($t->getPlayers() as $p) {

                if (!player_validation($p, $m))
                    continue;
            
                // Fetch player data from match
                $status = $p->getStatus($m->match_id);
                $mdat   = $p->getMatchData($m->match_id);

                // Print player row
                echo "<tr ";
                    if ($p->is_journeyman)    {echo 'style="background-color: '.COLOR_HTML_JOURNEY.'"';}
                    elseif ($status == 'MNG') {echo 'style="background-color: '.COLOR_HTML_MNG.'"';}
                echo " >\n";
                
                echo "<td>$p->nr</td>\n";
                echo "<td>$p->name</td>\n";
                echo "<td>$p->position" . ($status == 'MNG' ? '&nbsp;[MNG]' : '') . "</td>\n";
                echo "<td><input type='checkbox' " . ($mdat['mvp'] ? 'CHECKED ' : '') . (($DIS || ($status == 'MNG')) ? 'DISABLED' : '') . " name='mvp_$p->player_id'></td>\n";
                foreach (array('cp', 'td', 'intcpt', 'bh', 'si', 'ki') as $field) {
                    echo "<td><input ". (($DIS || ($status == 'MNG')) ? 'DISABLED' : '') . " type='text' onChange='numError(this);' size='1' maxlength='2' name='" . $field . "_$p->player_id' value='" . $mdat[$field] . "'></td>\n";
                }
                
                ?>
                <td>
                    <select name="inj_<?php echo $p->player_id;?>" <?php echo $DIS || $status == 'MNG' ? 'DISABLED' : ''; ?>>
                        <?php
                        echo "<option value='" . NONE . "' " .  ($mdat['inj'] == NONE ? 'SELECTED' : '') . ">None</option>\n";
                        echo "<option value='" . MNG . "' " .   ($mdat['inj'] == MNG ?  'SELECTED' : '') . ">MNG</option>\n";
                        echo "<option value='" . NI . "' " .    ($mdat['inj'] == NI ?   'SELECTED' : '') . ">Ni</option>\n";
                        echo "<option value='" . MA . "' " .    ($mdat['inj'] == MA ?   'SELECTED' : '') . ">Ma</option>\n";
                        echo "<option value='" . AV . "' " .    ($mdat['inj'] == AV ?   'SELECTED' : '') . ">Av</option>\n";
                        echo "<option value='" . AG . "' " .    ($mdat['inj'] == AG ?   'SELECTED' : '') . ">Ag</option>\n";
                        echo "<option value='" . ST . "' " .    ($mdat['inj'] == ST ?   'SELECTED' : '') . ">St</option>\n";
                        echo "<option value='" . DEAD . "' " .  ($mdat['inj'] == DEAD ? 'SELECTED' : '') . ">Dead!</option>\n";                                
                        ?>
                    </select>
                </td>
                <td>
                    <select name="agn1_<?php echo $p->player_id;?>" <?php echo $DIS || $status == 'MNG' ? 'DISABLED' : ''; ?>>
                        <?php
                        echo "<option value='" . NONE . "' " .  ($mdat['agn1'] == NONE ? 'SELECTED' : '') . ">None</option>\n";
                        echo "<option value='" . NI . "' " .    ($mdat['agn1'] == NI ? 'SELECTED' : '') . ">Ni</option>\n";
                        echo "<option value='" . MA . "' " .    ($mdat['agn1'] == MA ? 'SELECTED' : '') . ">Ma</option>\n";
                        echo "<option value='" . AV . "' " .    ($mdat['agn1'] == AV ? 'SELECTED' : '') . ">Av</option>\n";
                        echo "<option value='" . AG . "' " .    ($mdat['agn1'] == AG ? 'SELECTED' : '') . ">Ag</option>\n";
                        echo "<option value='" . ST . "' " .    ($mdat['agn1'] == ST ? 'SELECTED' : '') . ">St</option>\n";
                        ?>
                    </select>
                </td>
                <td>
                    <select name="agn2_<?php echo $p->player_id;?>" <?php echo $DIS || $status == 'MNG' ? 'DISABLED' : ''; ?>>
                        <?php
                        echo "<option value='" . NONE . "' " .  ($mdat['agn2'] == NONE ? 'SELECTED' : '') . ">None</option>\n";
                        echo "<option value='" . NI . "' " .    ($mdat['agn2'] == NI ? 'SELECTED' : '') . ">Ni</option>\n";
                        echo "<option value='" . MA . "' " .    ($mdat['agn2'] == MA ? 'SELECTED' : '') . ">Ma</option>\n";
                        echo "<option value='" . AV . "' " .    ($mdat['agn2'] == AV ? 'SELECTED' : '') . ">Av</option>\n";
                        echo "<option value='" . AG . "' " .    ($mdat['agn2'] == AG ? 'SELECTED' : '') . ">Ag</option>\n";
                        echo "<option value='" . ST . "' " .    ($mdat['agn2'] == ST ? 'SELECTED' : '') . ">St</option>\n";
                        ?>
                    </select>
                </td>
                </tr>
                <?php
            }
            ?>
            </table>
            <?php
            if ($rules['enable_stars_mercs']) {
                ?>
                <table style='border-spacing: 10px;'>
                    <tr>
                        <td align="left" valign="top">
                            <b>Star Players</b>: 
                            <input type='button' id="addStarsBtn_<?php echo $id;?>" value="<?php echo $lng->getTrn('secs/fixtures/report/add');?>" 
                            onClick="stars = document.getElementById('stars_<?php echo $id;?>'); addStarMerc(<?php echo $id;?>, stars.options[stars.selectedIndex].value);" <?php echo $DIS; ?>>
                            <select id="stars_<?php echo $id;?>" <?php echo $DIS; ?>>
                                <?php
                                foreach ($stars as $s => $d) {
                                    echo "<option ".((in_array($t->race, $d['teams'])) ? 'style="background-color: '.COLOR_HTML_READY.';"' : '')." value='$d[id]'>$s</option>\n";
                                }
                                ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <td align="left" valign="top">
                            <b>Mercenaries</b>: <input type='button' id="addMercsBtn_<?php echo $id;?>" value="<?php echo $lng->getTrn('secs/fixtures/report/add');?>" onClick="addStarMerc(<?php echo "$id, ".ID_MERCS;?>);" <?php echo $DIS; ?>>
                        </td>
                    </tr>
                </table>
                
                <table class='match_form' id='<?php echo "starsmercs_$id";?>'>
                </table>
                <?php
            }
        }
        ?>
        <table class='match_form'>
            <tr>
                <td class='seperator' colspan='13'></td>
            </tr>
            <tr>
                <td colspan='13' class='dark'><b><?php echo $lng->getTrn('secs/fixtures/report/summary');?></b></td>
            </tr>
            <tr>
                <td colspan='13'><textarea name='summary' rows='10' cols='100' <?php echo $DIS . ">" . $m->comment; ?></textarea></td>
            </tr>
            <tr>
                <td class='seperator' colspan='13'></td>
            </tr>
            <tr>
                <td colspan='13' class='dark'><b><?php echo $lng->getTrn('secs/fixtures/report/photos');?></b></td>
            </tr>
            <?php
            $rows = 3; // Number of rows of pics.
            $ppr = 4; // Pics per row.
            for ($pics = $m->getPics(), $i = 1; $i <= $rows; $i++) { // Limit to three rows of pics.
                echo "<tr><td>\n";
                for ($j = 1; $j <= $ppr && ($pic = array_shift($pics)); $j++) {
                    echo "<a href='handler.php?type=mg&amp;mid=$m->match_id&amp;pic=".(($i-1)*$ppr+$j)."'><img alt='match photo' src='$pic' width='220'></a>\n";
                }
                echo "</td></tr>\n";
            }
            ?>
            <tr>
                <td class='seperator' colspan='13'></td>
            </tr>
            <tr>
                <td class='seperator' colspan='13'><?php echo $lng->getTrn('secs/fixtures/report/pnote');?></td>
            </tr>

            <?php
            for ($i = 1; $i <= 10; $i++) {
                echo "<tr><td>".$lng->getTrn('secs/fixtures/report/photo')." #$i: <input $DIS type='file' name='img$i'>".(($m->picExists($i)) ? '&nbsp;&nbsp;<font color="orange"><b>'.$lng->getTrn('secs/fixtures/report/occ').'</b></font>' : '')."</td></tr>\n";
            }
            ?>
        </table>
        <br>
        <center>
            <input type="submit" name='button' value="<?php echo $lng->getTrn('secs/fixtures/report/save');?>" <?php echo $DIS; ?>>
        </center>
    </form>
    <br><br>
    <?php
    $CDIS = (!is_object($coach)) ? 'DISABLED' : '';
    ?>
    <form method="POST">
        <table class="match_form">
            <tr>
                <td colspan='13' class='dark'><b><a href="javascript:void(0)" onclick="obj=document.getElementById('msmrc'); if (obj.style.display != 'none'){obj.style.display='none'}else{obj.style.display='block'};">[+/-]</a> <?php echo $lng->getTrn('secs/fixtures/report/msmrc');?></b></td>
            </tr>
            <tr>
                <td class='seperator'></td>
            </tr>
            <tr>
                <td>
                    <div id="msmrc">
                        <?php echo $lng->getTrn('secs/fixtures/report/existCmt');?>: <?php if (!$m->hasComments()) echo '<i>'.$lng->getTrn('secs/fixtures/report/none').'</i>';?><br><br>
                        <?php
                        foreach ($m->getComments() as $c) {
                            echo "Posted $c->date by <b>$c->sname</b>:<br>".$c->txt."<br><br>\n";
                        }
                        ?>
                    </div>
                </td>
            </tr>
            <tr>
                <td>
                    <?php echo $lng->getTrn('secs/fixtures/report/newCmt');?>:<br>
                    <textarea name="msmrc" rows='5' cols='100' <?php echo $CDIS;?>><?php echo $lng->getTrn('secs/fixtures/report/writeNewCmt');?></textarea>
                    <br>
                    <input type="submit" value="<?php echo $lng->getTrn('secs/fixtures/report/postCmt');?>" name="new_msmrc" <?php echo $CDIS;?>>
                </td>
            </tr>
        </table>
    </form>
    <script language='JavaScript' type='text/javascript'>
        document.getElementById('msmrc').style.display = 'none';
    </script>
    <?php
    
    /* 
        Now, we call javascript routine(s) to fill out stars and mercs rows, if such entries exist in database. 
    */
    
    $i = 0; // Counter. Used to pass PHP-data to Javascript.
    foreach (array(1 => $team1->team_id, 2 => $team2->team_id) as $id => $t) {
        foreach (Star::getStars($t, $m->match_id, false) as $s) {
            $s->setStats(false, $m->match_id, false); // Set the star's stats fields to the saved values in the database for this match.
            echo "<script language='JavaScript' type='text/javascript'>\n";
            echo "var mdat$i = [];\n";
            foreach (array('mvp', 'cp', 'td', 'intcpt', 'bh', 'ki', 'si') as $f) {
                echo "mdat${i}['$f'] = ".$s->$f.";\n";
            }
            echo "existingStarMerc($id, $s->star_id, mdat$i);\n";
            echo "</script>\n";
            $i++;
        }
        
        foreach (Mercenary::getMercsHiredByTeam($t, $m->match_id) as $merc) {
            echo "<script language='JavaScript' type='text/javascript'>\n";
            echo "var mdat$i = [];\n";
            foreach (array('mvp', 'cp', 'td', 'intcpt', 'bh', 'ki', 'si', 'skills') as $f) {
                echo "mdat${i}['$f'] = ".$merc->$f.";\n";
            }
            echo "existingStarMerc($id, ".ID_MERCS.", mdat$i);\n";
            echo "</script>\n";
            $i++;
        }
    }
}

function player_validation($p, $m) {

    if (!is_object($p) || !is_object($m))
        return false;
        
    // Existing match?                    
    if ($m->is_played) {

        // Skip if player is bought after match was played.
        if ($p->date_bought > $m->date_played)
            return false;
    
        // If sold before this match was played.
        if ($p->is_sold && $p->date_sold < $m->date_played)
            return false;
        
        // Player died in a earlier match.
        if ($p->getStatus($m->match_id) == 'DEAD')
            return false;
    }
    // New match?
    else {
    
        if ($p->is_dead || $p->is_sold)
            return false;
    }
    
    return true;
}

function unscheduled_match_form($tour_id) {
	
	// Is $tour_id valid?
    if (!get_alt_col('tours', 'tour_id', $tour_id, 'tour_id'))
        fatal("Invalid tournament ID.");
    
    global $stars;
    global $rules;
    global $lng;
    
    /****************
     *
     * Upload match report ?
     *
     ****************/
    
    if (isset($_POST['upload']) && $_POST['upload']=="true") {
    	
    	// Create new match
    	$coach = (isset($_SESSION['logged_in'])) ? new Coach($_SESSION['coach_id']) : null;
    	$tour = new Tour($tour_id);
    	
    	// This moves the match report in the right folder with the right name
	    $rand = mt_rand();
	    $id = "match_report_" .$rand. ".db";
	    uploadMatchReport("uploaded_match_report", UPLOAD_MATCH_REPORT_DIR, $id);
	    
	    $match_report = new CyanideMatchReport(UPLOAD_MATCH_REPORT_DIR ."/". $id);
	    $home_team_name = mysql_real_escape_string($match_report->home_team_name);
	    $away_team_name = mysql_real_escape_string($match_report->away_team_name);
    	
    	@unlink(UPLOAD_MATCH_REPORT_DIR . "/" . $id);
    	
    	$team1_id = Team::getTeam($home_team_name);
    	$team2_id = Team::getTeam($away_team_name);
    	$round=RT_STANDALONE;
    	$f_tour_id=$tour->tour_id;
    	$team1 = new Team($team1_id);
    	$team2 = new Team($team2_id);
    	
    	// Determine visitor privileges.
    	$ALLOW_EDIT = false;
   		if (is_object($coach) && ($coach->admin || $team1->owned_by_coach_id==$coach->coach_id || $team2->owned_by_coach_id==$coach->coach_id))
        	$ALLOW_EDIT = true;
    	
  		if ($ALLOW_EDIT) {
	    	$input = array('team1_id' => $team1_id, 'team2_id' => $team2_id, 'round' => $round, 'f_tour_id' => $f_tour_id);
	    	
	    	status(Match::create($input));
	    	
	    	$query = "SELECT match_id FROM matches 
	            WHERE date_played IS NULL AND f_tour_id=" .$f_tour_id. " AND round=" .$round. " AND team1_id=" .$team1_id. " AND team2_id=" .$team2_id
	    		." ORDER BY match_id DESC";
	        $result = mysql_query($query);
	        
	    	if ($result && mysql_num_rows($result) > 0) {
	            $row = mysql_fetch_assoc($result);
	            $m = new Match($row['match_id']);
	            
	            prepareForm($match_report, $m, $team1, $team2);
	            submit_form($m, $team1, $team2, $coach, $stars, $row['match_id']);
	            
	            // Redirect to the match report edit page
	            ?>
		            <script type="text/javascript">
					<!--
					window.location = "index.php?section=fixturelist&match_id=<?php echo $m->match_id; ?>";
					//-->
					</script>
				<?php
	            
	        }
  		}
  		else {
  			title ($lng->getTrn('secs/fixtures/forbidden'));
  		}
  		
    }

    /****************
     *
     * Generate form 
     *
     ****************/
    
    title($lng->getTrn('secs/cc/main/new_unscheduled_match'));
    $CP = 6; // Colspan.
	
	?>
	<form method="POST" enctype="multipart/form-data" action="index.php?section=fixturelist&amp;match_id=new&amp;tour_id=<?php echo $tour_id; ?>" >
	    	<table class="match_form">
	    		<tr>
	                <td colspan="<?php echo $CP;?>" class="dark"><b><?php echo $lng->getTrn('secs/cc/main/upload_match_report');?></b></td>
	            </tr>
	            <tr><td class='seperator' colspan='<?php echo $CP;?>'></td></tr>
	            <tr>
	                <td colspan='<?php echo $CP;?>'>
	                	<input name="uploaded_match_report" type="file"/>
	                	<input type="hidden" name="upload" value="true"/>
	                	<input type="submit" value="<?php echo $lng->getTrn('secs/cc/main/upload_match_report');?>" />
	                </td>
	            </tr>
	            <tr><td class='seperator' colspan='<?php echo $CP;?>'></td></tr>
	       </table>
	</form>
	<?php
	
}

function submit_form ($m, $team1, $team2, $coach, $stars, $match_id) {
	
		if (get_magic_quotes_gpc())
            $_POST['summary'] =  stripslashes($_POST['summary']);
        
        // Update general match data
        status($m->update(array(
            'submitter_id'  => $_SESSION['coach_id'],
            'stadium'       => $_POST['stadium'],
            'gate'          => (int) ($_POST['gate'] * 1000),
            'fans'          => (int) $_POST['fans'],
            'ffactor1'      => $_POST['ff_1'],
            'ffactor2'      => $_POST['ff_2'],
            'income1'       => $_POST['inc_1'] ? $_POST['inc_1'] * 1000 : 0,
            'income2'       => $_POST['inc_2'] ? $_POST['inc_2'] * 1000 : 0,
            'team1_score'   => $_POST['result1'] ? $_POST['result1'] : 0,
            'team2_score'   => $_POST['result2'] ? $_POST['result2'] : 0,
            'smp1'          => (int) $_POST['smp1'],
            'smp2'          => (int) $_POST['smp2'],
            'tcas1'         => (int) $_POST['tcas1'],
            'tcas2'         => (int) $_POST['tcas2'],
            'comment'       => $_POST['summary'] ? $_POST['summary'] : '',
        )));

        // Pictures.
        $m->savePics();

        // Update match's player data
        foreach (array(1 => $team1, 2 => $team2) as $id => $t) {
        
            /* Save ordinary players */
        
            foreach ($t->getPlayers() as $p) {
            
                if (!player_validation($p, $m))
                    continue;
                
                // Set zero entry for MNG player(s).
                if ($p->getStatus($m->match_id) == 'MNG') {
                    $_POST['mvp_' . $p->player_id]      = 0;
                    $_POST['cp_' . $p->player_id]       = 0;
                    $_POST['td_' . $p->player_id]       = 0;
                    $_POST['intcpt_' . $p->player_id]   = 0;
                    $_POST['bh_' . $p->player_id]       = 0;
                    $_POST['si_' . $p->player_id]       = 0;
                    $_POST['ki_' . $p->player_id]       = 0;
                    $_POST['inj_' . $p->player_id]      = NONE;
                    $_POST['agn1_' . $p->player_id]     = NONE;
                    $_POST['agn2_' . $p->player_id]     = NONE;
                }
                
                $m->entry(array(
                    'player_id' => $p->player_id,
                    /* 
                        Regarding MVP: We must check for isset() since checkboxes are not sent at all when not checked! 
                        We must also test for truth since the MNG-status exception above defines the MNG status, and thereby passing isset() here!
                    */
                    'mvp'     => (isset($_POST['mvp_' . $p->player_id]) && $_POST['mvp_' . $p->player_id]) ? 1 : 0,
                    'cp'      => $_POST['cp_' . $p->player_id],
                    'td'      => $_POST['td_' . $p->player_id],
                    'intcpt'  => $_POST['intcpt_' . $p->player_id],
                    'bh'      => $_POST['bh_' . $p->player_id],
                    'si'      => $_POST['si_' . $p->player_id],
                    'ki'      => $_POST['ki_' . $p->player_id],
                    'inj'     => $_POST['inj_' . $p->player_id],
                    'agn1'    => $_POST['agn1_' . $p->player_id],
                    'agn2'    => $_POST['agn2_' . $p->player_id],
                ));
            }
            
            /* 
                Save stars entries. 
                Note: These entries are not saved through the match class as above. 
                It was simpler to implement the routine in the star class.
            */

            foreach ($stars as $star) {
                $s = new Star($star['id']);
                if (isset($_POST['team_'.$star['id']]) && $_POST['team_'.$star['id']] == $id) {
                    $sid = $s->star_id;

                    $s->mkMatchEntry($m->match_id, $t->team_id, array(
                        'mvp'     => (isset($_POST["mvp_$sid"]) && $_POST["mvp_$sid"]) ? 1 : 0,
                        'cp'      => $_POST["cp_$sid"],
                        'td'      => $_POST["td_$sid"],
                        'intcpt'  => $_POST["intcpt_$sid"],
                        'bh'      => $_POST["bh_$sid"],
                        'si'      => $_POST["si_$sid"],
                        'ki'      => $_POST["ki_$sid"],
                    ));
                }
                else {
                    $s->rmMatchEntry($m->match_id, $t->team_id);
                }
            }
            
            /* 
                Save mercenary entries. 
                Note: These entries are not saved through the match class as above. 
                It was simpler to implement the routine in the mercenary class.
            */
            
            Mercenary::rmMatchEntries($m->match_id, $t->team_id); // Remove all previously saved mercs in this match.
            for ($i = 0; $i <= 50; $i++)  { # We don't expect over 50 mercs. This is just some large random number.
                $idm = '_'.ID_MERCS.'_'.$i;
                if (isset($_POST["team$idm"]) && $_POST["team$idm"] == $id) {
                    Mercenary::mkMatchEntry($m->match_id, $i, $t->team_id, array(
                        'mvp'     => (isset($_POST["mvp$idm"]) && $_POST["mvp$idm"]) ? 1 : 0,
                        'cp'      => $_POST["cp$idm"],
                        'td'      => $_POST["td$idm"],
                        'intcpt'  => $_POST["intcpt$idm"],
                        'bh'      => $_POST["bh$idm"],
                        'si'      => $_POST["si$idm"],
                        'ki'      => $_POST["ki$idm"],
                        'skills'  => $_POST["skills$idm"],
                    ));
                }
            }
        }

        // Update tournament
        $tour = new Tour($m->f_tour_id);
        $tour->update();
        
        // Refresh objects used to display form.
        $m = new Match($match_id);
        $team1 = new Team($m->team1_id);
        $team2 = new Team($m->team2_id);
}

function prepareForm ($match_report, $m, $team1, $team2) {
	
		// Prepare POST values for the form
		$_POST['gate'] = $match_report->spectators/1000;
    	$_POST['result1'] = $match_report->home_team_score;
    	$_POST['tcas1'] = $match_report->home_team_inflicted_cas;
    	$_POST['result2'] = $match_report->away_team_score;
    	$_POST['tcas2'] = $match_report->away_team_inflicted_cas;
    	
    	$_POST['ff_1'] = $match_report->home_team_ff_variation;
    	$_POST['inc_1'] = $match_report->home_team_cash_earned;
    	$_POST['ff_2'] = $match_report->away_team_ff_variation;
    	$_POST['inc_2'] = $match_report->away_team_cash_earned;
    	
    	$_POST['stadium'] = $m->team1_id;
    	$_POST['summary'] = "";
    	$_POST['fans'] = $match_report->home_team_fans + $match_report->away_team_fans;
    	$_POST['smp1'] = 0;
    	$_POST['smp2'] = 0;
    	
    	// Players
    	$home_players_data = $match_report->home_team_player_data;
    	foreach ($home_players_data as $player_data) {
    		$players = $team1->getPlayers();
    		foreach ($players as $player) {
    			if ($player->name==$player_data['name']) {
    				$_POST['mvp_' .$player->player_id] = $player_data['mvp'];
    				$_POST['cp_' .$player->player_id] = $player_data['cp'];
    				$_POST['td_' .$player->player_id] = $player_data['td'];
    				$_POST['intcpt_' .$player->player_id] = $player_data['int'];
    				$_POST['bh_' .$player->player_id] = $player_data['cas'];
    				$_POST['ki_' .$player->player_id] = $player_data['ki'];
    				$_POST['inj_' .$player->player_id] = $player_data['inj'];
    				
    				$_POST['si_' .$player->player_id] = 0;
    				$_POST['agn1_' .$player->player_id] = NONE;
    				$_POST['agn2_' .$player->player_id] = NONE;
    			}
    		}
    	}
    	
    	$away_players_data = $match_report->away_team_player_data;
    	foreach ($away_players_data as $player_data) {
    		$players = $team2->getPlayers();
    		foreach ($players as $player) {
    			if ($player->name==$player_data['name']) {
    				$_POST['mvp_' .$player->player_id] = $player_data['mvp'];
    				$_POST['cp_' .$player->player_id] = $player_data['cp'];
    				$_POST['td_' .$player->player_id] = $player_data['td'];
    				$_POST['intcpt_' .$player->player_id] = $player_data['int'];
    				$_POST['bh_' .$player->player_id] = $player_data['cas'];
    				$_POST['ki_' .$player->player_id] = $player_data['ki'];
    				$_POST['inj_' .$player->player_id] = $player_data['inj'];
    				
    				$_POST['si_' .$player->player_id] = 0;
    				$_POST['agn1_' .$player->player_id] = NONE;
    				$_POST['agn2_' .$player->player_id] = NONE;
    			}
    		}
    	}
}

