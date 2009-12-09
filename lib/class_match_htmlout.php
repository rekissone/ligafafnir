<?php

/*
 *  Copyright (c) Nicholas Mossor Rathmann <nicholas.rathmann@gmail.com> 2009. All Rights Reserved.
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

class Match_HTMLOUT extends Match
{

const T_HTML_MATCHES_PER_PAGE = 100;

// Fields used in match reports
# Stored name => display name
# Don't touch - order denpendant entries!!!
private static $fields_rel = array('nr' => 'Nr', 'name' => 'Name', 'pos' => 'Position',);
private static $fields_ach = array('mvp' => 'MVP', 'cp' => 'Cp', 'td' => 'Td', 'intcpt' => 'Int', 'bh' => 'BH', 'si' => 'SI', 'ki' => 'Ki',);
private static $fields_inj = array('inj' => 'Inj', 'agn1' => 'Ageing 1', 'agn2' => 'Ageing 2');

function recentMatches() {

    global $lng;
    title($lng->getTrn('menu/matches_menu/recent'));
    list($node, $node_id) = HTMLOUT::nodeSelector(false,false,'');
    echo '<br>';
    HTMLOUT::recentGames(false,false,$node,$node_id, false,false,array('url' => 'index.php?section=matches&amp;type=recent', 'n' => MAX_RECENT_GAMES));
}

function upcommingMatches() {

    global $lng;
    title($lng->getTrn('menu/matches_menu/upcomming'));
    list($node, $node_id) = HTMLOUT::nodeSelector(false,false,'');
    echo '<br>';
    HTMLOUT::upcommingGames(false,false,$node,$node_id, false,false,array('url' => 'index.php?section=matches&amp;type=upcomming', 'n' => MAX_RECENT_GAMES));
}

public static function tourMatches() 
{
    global $lng, $coach;
    
    // Admin actions made?
    if (isset($_GET['action']) && is_object($coach) && $coach->admin) {
        $match = new Match($_GET['mid']);
        switch ($_GET['action'])
        {
            case 'lock':   status($match->setLocked(true)); break;
            case 'unlock': status($match->setLocked(false)); break;
            case 'delete': status($match->delete()); break;
            case 'reset':  status($match->reset()); break;            
        }
    }
    
    ?>
    <script language="JavaScript" type="text/javascript">
        function match_delete() {
            return confirm('<?php echo $lng->getTrn('matches/tourmatches/matchdelete'); ?>');
        }
        function match_reset() {
            return confirm('<?php echo $lng->getTrn('matches/tourmatches/reset_notice'); ?>');
        }
    </script>
    <?php
    
    $trid = $_GET['trid']; # Shortcut for string interpolation.
    $query = "SELECT COUNT(*) FROM matches WHERE f_tour_id = $trid";
    $result = mysql_query($query);
    list($cnt) = mysql_fetch_row($result);
    $pages = ($cnt == 0) ? 1 : ceil($cnt/self::T_HTML_MATCHES_PER_PAGE);
    global $page;
    $page = isset($_GET['page']) ? $_GET['page'] : 1; # Page 1 is default, of course.    
    $_url = "?section=matches&amp;type=tourmatches&amp;trid=$trid&amp;";
    title(get_alt_col('tours', 'tour_id', $trid, 'name'));
    echo '<center><table>';
    echo '<tr><td>';
    echo 'Page: '.implode(', ', array_map(create_function('$nr', 'global $page; return ($nr == $page) ? $nr : "<a href=\''.$_url.'page=$nr\'>$nr</a>";'), range(1,$pages)));
    echo '</td></td>';
    echo "<tr><td>    Matches: $cnt</td></td>";
    echo '</table></center>';
    
    $rnd = 0; # Initial round number must be lower than possible round numbers.    
    $cols = 7; # Common columns counter.
    $query = "SELECT t1.name AS 't1_name', t1.team_id AS 't1_id', t2.name AS 't2_name', t2.team_id AS 't2_id', match_id, date_played, locked, round, team1_score, team2_score 
        FROM matches, teams AS t1, teams AS t2 WHERE f_tour_id = $trid AND team1_id = t1.team_id AND team2_id = t2.team_id 
        ORDER BY round DESC, date_played DESC, date_created ASC LIMIT ".(($page-1)*self::T_HTML_MATCHES_PER_PAGE).', '.(($page)*self::T_HTML_MATCHES_PER_PAGE);
    $result = mysql_query($query);
    echo "<table class='tours'>\n";
    while ($m = mysql_fetch_object($result)) {
        if ($m->round != $rnd) {
            $rnd = $m->round;
            $round = '';
            if     ($rnd == RT_FINAL)         $round = $lng->getTrn('matches/tourmatches/roundtypes/final');
            elseif ($rnd == RT_3RD_PLAYOFF)   $round = $lng->getTrn('matches/tourmatches/roundtypes/thirdPlayoff');
            elseif ($rnd == RT_SEMI)          $round = $lng->getTrn('matches/tourmatches/roundtypes/semi');
            elseif ($rnd == RT_QUARTER)       $round = $lng->getTrn('matches/tourmatches/roundtypes/quarter');
            elseif ($rnd == RT_ROUND16)       $round = $lng->getTrn('matches/tourmatches/roundtypes/rnd16');
            else                              $round = $lng->getTrn('matches/tourmatches/roundtypes/rnd').": $rnd";
            echo "<tr><td colspan='$cols' class='seperator'></td></tr>";
            echo "<tr><td colspan='$cols' class='round'><center><b>$round</b></center></td></tr>";
            echo "<tr><td colspan='$cols' class='seperator'></td></tr>";
        }
        ?>
        <tr>
            <td><?php echo !empty($m->date_played) ? textdate($m->date_played, true) : ''; ?></td>
            <td class="match" style="text-align: right;"><?php echo $m->t1_name;?></td>
            <td class="match" style="text-align: center;"><?php echo !empty($m->date_played) ? $m->team1_score : '';?></td>
            <td class="match" style="text-align: center;">-</td>
            <td class="match" style="text-align: center;"><?php echo !empty($m->date_played) ? $m->team2_score : '';?></td>
            <td class="match" style="text-align: left;"><?php echo $m->t2_name;?></td>
            <?php
            // Does the user have edit or view rights?
            $matchURL = "index.php?section=matches&amp;type=tourmatches&amp;trid=$trid&amp;mid=$m->match_id";
            ?>
            <td>
            <?php
            echo "&nbsp;<a href='index.php?section=matches&amp;type=report&amp;mid=$m->match_id'>".$lng->getTrn('common/view')."</a>&nbsp;\n";
            if (is_object($coach) && $coach->admin) {
                echo "<a onclick=\"return match_reset();\" href='$matchURL&amp;action=reset'>".$lng->getTrn('common/reset')."</a>&nbsp;\n";
                echo "<a onclick=\"return match_delete();\" href='$matchURL&amp;action=delete' style='color:".(!empty($m->date_played) ? 'Red' : 'Blue').";'>".$lng->getTrn('common/delete')."</a>&nbsp;\n";
                echo "<a href='$matchURL&amp;action=".(($m->locked) ? 'unlock' : 'lock')."'>" . ($m->locked ? $lng->getTrn('common/unlock') : $lng->getTrn('common/lock')) . "</a>&nbsp;\n";
            }
            ?>
            </td>
        </tr>
        <?php
    }
    echo "</table>\n";
}

public static function tours() 
{

    global $rules, $settings, $lng;

    title($lng->getTrn('menu/matches_menu/tours'));

    $query = "SELECT lid,did,tour_id,locked,
        tours.name AS 'tours.name',divisions.name AS 'divisions.name',leagues.name AS 'leagues.name'
        FROM tours,divisions,leagues WHERE tours.f_did = divisions.did AND divisions.f_lid = leagues.lid
        ORDER BY leagues.lid ASC, divisions.did ASC, tours.tour_id ASC";
    $result = mysql_query($query);
    $flist = array();
    while ($row = mysql_fetch_object($result)) {
        $flist[$row->lid][$row->did][$row->tour_id] = $row;
        $flist[$row->lid]['info']            = array('name' => $row->{'leagues.name'});
        $flist[$row->lid][$row->did]['info'] = array('name' => $row->{'divisions.name'});
    }

    // Print fixture list.
    echo "<table class='tours'>\n";
    foreach ($flist as $lid => $divs) {
        echo "<tr class='leauges'><td><b>
        <a href='javascript:void(0);' onClick=\"slideToggleFast('lid_$lid');\"><b>[+/-]</b></a>&nbsp;
        ".$flist[$lid]['info']['name']."
        </b></td></tr>";
        echo "<tr><td><div id='lid_$lid'>";
    foreach ($divs as $did => $tours) {
        if ($did == 'info') continue;
        echo "<table class='tours'>\n";
        echo "<tr class='divisions'><td><b>
        <a href='javascript:void(0);' onClick=\"slideToggleFast('did_$did');\"><b>[+/-]</b></a>&nbsp;
        ".$flist[$lid][$did]['info']['name']."
        </b></td></tr>";
        echo "<tr><td><div id='did_$did'>";
    foreach ($tours as $trid => $mergedObj) {
        if ($trid == 'info') continue;
        ?>
        <table class='tours'>
            <tr class='tours'>
                <td>
                    &nbsp;&nbsp;<a href='index.php?section=matches&amp;type=tourmatches&amp;trid=<?php echo $trid;?>'><b><?php echo $mergedObj->{'tours.name'};?></b></a>
                    <?php
                    $tr = new Tour($trid);
                    $suffix = '';
                    if ($tr->is_finished) { $suffix .= '-&nbsp;&nbsp;<i>'.$lng->getTrn('common/finished').'</i>&nbsp;&nbsp;';}
                    if ($tr->locked)      { $suffix .= '-&nbsp;&nbsp;<i>'.$lng->getTrn('common/locked').'</i>&nbsp;&nbsp;';}
                    if (!empty($suffix)) { echo '&nbsp;&nbsp;'.$suffix;}
                    ?>
                </td>
            </tr>
        </table>
        <?php
    }
    echo "</div></td></tr></table>\n";
    }
    echo "</div></td></tr>\n";
    }
    echo "</table>\n";
}

public static function report() {

    // Is $match_id valid?
    $match_id = $_GET['mid'];
    if (!get_alt_col('matches', 'match_id', $match_id, 'match_id'))
        fatal("Invalid match ID.");
    
    global $lng, $stars, $rules, $coach;
    
    // Create objects
    $m = new Match($match_id);
    $team1 = new Team($m->team1_id);
    $team2 = new Team($m->team2_id);
    
    // Determine visitor privileges.
    $ALLOW_EDIT = (!$m->locked && is_object($coach) && ($coach->admin || $coach->isInMatch($m->match_id)));
    $DIS = ($ALLOW_EDIT) ? '' : 'DISABLED';

    // Relay to ES report page?
    if (isset($_GET['es_report'])) { # Don't care what value the GET field has!
        self::report_ES($match_id, $ALLOW_EDIT);
        return;
    }

    $easyconvert = new array_to_js();
    @$easyconvert->add_array($stars, 'phpStars'); // Load stars array into JavaScript array.
    echo $easyconvert->output_all();

    echo '<script language="JavaScript" type="text/javascript">
    var ID_MERCS = '.ID_MERCS.';
    var ID_STARS_BEGIN = '.ID_STARS_BEGIN.';    
    </script>
    ';

    /*****************
     *
     * Submitted form?
     *
     *****************/
     
    if (isset($_POST['button']) && $ALLOW_EDIT) {
    
        if (get_magic_quotes_gpc())
            $_POST['summary'] =  stripslashes($_POST['summary']);
        
        MTS('Report submit STARTED');
        
        // Update general match data
        status($m->update(array(
            'submitter_id'  => $_SESSION['coach_id'],
            'stadium'       => $_POST['stadium'],
            'gate'          => (int) ($_POST['gate'] * 1000),
            'fans'          => (int) $_POST['fans'],
            'ffactor1'      => $_POST['ff1'],
            'ffactor2'      => $_POST['ff2'],
            'income1'       => (int) $_POST['inc1'] * 1000,
            'income2'       => (int) $_POST['inc2'] * 1000,
            'team1_score'   => $_POST['result1'] ? $_POST['result1'] : 0,
            'team2_score'   => $_POST['result2'] ? $_POST['result2'] : 0,
            'smp1'          => (int) $_POST['smp1'],
            'smp2'          => (int) $_POST['smp2'],
            'tcas1'         => (int) $_POST['tcas1'],
            'tcas2'         => (int) $_POST['tcas2'],
            'fame1'         => (int) $_POST['fame1'],
            'fame2'         => (int) $_POST['fame2'],
            'tv1'           => (int) $_POST['tv1']*1000,
            'tv2'           => (int) $_POST['tv2']*1000,
            'comment'       => $_POST['summary'] ? $_POST['summary'] : '',
        )));
        MTS('matches entry submitted');

        // Update match's player data
        foreach (array(1 => $team1, 2 => $team2) as $id => $t) {
        
            /* Save ordinary players */
        
            foreach ($t->getPlayers() as $p) {
            
                if (!self::player_validation($p, $m))
                    continue;
                
                // We create zero entries for MNG player(s). This is required!
                if ($p->getStatus($m->match_id) == MNG) {
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
                    'team_id'   => $t->team_id,
                    // Regarding MVP: We must check for isset() since checkboxes are not sent at all when not checked! 
                    'mvp'     => (isset($_POST['mvp_' . $p->player_id])) ? 1 : 0,
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
            MTS('Saved all REGULAR player entries in match_data for team '.$id);
            
            /* 
                Save stars entries. 
            */

            foreach ($stars as $star) {
                $s = new Star($star['id']);
                if (isset($_POST['team_'.$star['id']]) && $_POST['team_'.$star['id']] == $id) {
                    $sid = $s->star_id;

                    $m->entry(array(
                        'player_id' => $sid,
                        'team_id'   => $t->team_id,
                        
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
            MTS('Saved all STAR player entries in match_data for team '.$id);
            
            /* 
                Save mercenary entries. 
            */
            
            Mercenary::rmMatchEntries($m->match_id, $t->team_id); // Remove all previously saved mercs in this match.
            for ($i = 0; $i <= 50; $i++)  { # We don't expect over 50 mercs. This is just some large random number.
                $idm = '_'.ID_MERCS.'_'.$i;
                if (isset($_POST["team$idm"]) && $_POST["team$idm"] == $id) {
                    $m->entry(array(
                        'player_id' => ID_MERCS,
                        'team_id'   => $t->team_id,
                        'nr'        => $i,
                        'skills'    => $_POST["skills$idm"],                    
                        
                        'mvp'     => (isset($_POST["mvp$idm"]) && $_POST["mvp$idm"]) ? 1 : 0,
                        'cp'      => $_POST["cp$idm"],
                        'td'      => $_POST["td$idm"],
                        'intcpt'  => $_POST["intcpt$idm"],
                        'bh'      => $_POST["bh$idm"],
                        'si'      => $_POST["si$idm"],
                        'ki'      => $_POST["ki$idm"],
                    ));
                }
            }
            MTS('Saved all STAR player entries in match_data for team '.$id);
        }
        
        $m->finalizeMatchSubmit(); # Required!
        MTS('Report submit ENDED');

        // Refresh objects used to display form.
        $m = new Match($match_id);
        $team1 = new Team($m->team1_id);
        $team2 = new Team($m->team2_id);
    }
    
    // Match comment made?
    if (isset($_POST['msmrc']) && is_object($coach)) {
        status($m->newComment($coach->coach_id, $_POST['msmrc']));
    }
    
    // Match comment delete?
    if (isset($_POST['type']) && $_POST['type'] == 'cmtdel' && is_object($coach)) {
        status($m->deleteComment($_POST['cid']));
    }

    /****************
     *
     * Generate form 
     *
     ****************/

    title("$m->team1_name - $m->team2_name");
    $CP = 8; // Colspan.

    if (Module::isRegistered('UPLOAD_BOTOCS')) {
        Print "<center><a href='http://".$_SERVER["SERVER_NAME"]."/handler.php?type=leegmgr&amp;replay=".$m->match_id."'>view replay</a></center>";
    }

    ?>
    <table>
    <tr><td><b><?php echo $lng->getTrn('common/league');?></b>:</td><td><?php       echo get_parent_name(T_NODE_MATCH, $m->match_id, T_NODE_LEAGUE);?></td></tr>
    <tr><td><b><?php echo $lng->getTrn('common/division');?></b>:</td><td><?php     echo get_parent_name(T_NODE_MATCH, $m->match_id, T_NODE_DIVISION);?></td></tr>
    <tr><td><b><?php echo $lng->getTrn('common/tournament');?></b>:</td><td><?php   echo get_parent_name(T_NODE_MATCH, $m->match_id, T_NODE_TOURNAMENT);?></td></tr>
    <tr><td><b><?php echo $lng->getTrn('common/dateplayed');?></b>:</td><td><?php   echo ($m->is_played) ? textdate($m->date_played) : '<i>'.$lng->getTrn('matches/report/notplayed').'</i>';?></td></tr>
    </table>
    <br>
    <?php HTMLOUT::helpBox($lng->getTrn('matches/report/help'), $lng->getTrn('common/needhelp')); ?>
    <form method="POST" enctype="multipart/form-data">
        <table class="common">
            <tr class='commonhead'><td colspan="<?php echo $CP;?>"><b><?php echo $lng->getTrn('matches/report/info');?></b></td></tr>
            <tr><td class='seperator' colspan='<?php echo $CP;?>'></td></tr>
            <tr><td colspan='<?php echo $CP;?>'>
                <b><?php echo $lng->getTrn('matches/report/stadium');?></b>&nbsp;
                <select name="stadium" <?php echo $DIS;?>>
                    <?php
                    $stad = ($m->stadium) ? $m->stadium : $m->team1_id;
                    foreach (array($team1, $team2) as $_t) {
                        echo "<option value='$_t->team_id'".(($stad == $_t->team_id) ? 'SELECTED' : '').">$_t->name</option>\n";
                    }
                    ?>
                </select>
            </td></tr>
            <tr><td colspan='<?php echo $CP;?>'>
                <b><?php echo $lng->getTrn('matches/report/gate');?></b>&nbsp;
                <input type="text" name="gate" value="<?php echo $m->gate ? $m->gate/1000 : 0;?>" size="4" maxlength="4" <?php echo $DIS;?>>k
            </td></tr>
            <tr><td colspan='<?php echo $CP;?>'>
                <b><?php echo $lng->getTrn('matches/report/fans');?></b>&nbsp;
                <input type="text" name="fans" value="<?php echo $m->fans;?>" size="7" maxlength="12" <?php echo $DIS;?>>
            </td></tr>
            <tr><td colspan='<?php echo $CP;?>'>
                <b>E</b>xtra player <b>S</b>tats (ES) <a href="index.php?section=matches&amp;type=report&amp;mid=<?php echo $m->match_id?>&amp;es_report=1">report page here</a>
            </td></tr>
            <tr><td class="seperator" colspan='<?php echo $CP;?>'></td></tr>
            <tr class='commonhead'>
                <td><b><?php echo $lng->getTrn('matches/report/teams');?></b></td>
                <td><b><?php echo $lng->getTrn('matches/report/score');?></b></td>
                <td><b>&Delta; <?php echo $lng->getTrn('matches/report/treas');?></b></td>
                <td><b><?php echo $lng->getTrn('matches/report/ff');?></b></td>
                <td><b><?php echo $lng->getTrn('matches/report/smp');?></b></td>
                <td><b><?php echo $lng->getTrn('matches/report/tcas');?></b></td>
                <td><b><?php echo $lng->getTrn('matches/report/fame');?></b></td>
                <td><b><?php echo $lng->getTrn('matches/report/tv');?></b></td>
            </tr>
            
            <tr><td class='seperator' colspan='<?php echo $CP;?>'></td></tr>
            <?php
            foreach (array(1,2) as $N) {
                echo "<tr>\n";
                echo "<td>".${"team$N"}->name."</td>\n";
                echo "<td><input type='text' name='result$N' value='".((int) $m->{"team${N}_score"})."' size='1' maxlength='2' $DIS></td>\n";
                echo "<td><input type='text' name='inc$N' value='".(((int) $m->{"income$N"})/1000)."' size='4' maxlength='4' $DIS>k</td>\n";
                echo "<td>";
                foreach (array('1' => 'green', '0' => 'blue', '-1' => 'red') as $Nff => $color) {
                    echo "<input $DIS type='radio' name='ff$N' value='$Nff' ".(($m->{"ffactor$N"} == (int) $Nff) ? 'CHECKED' : '')."><font color='$color'><b>$Nff</b></font>";
                }
                echo "</td>\n";
                echo "<td><input type='text' name='smp$N' value='".($m->{"smp$N"})."' size='1' maxlength='2' $DIS>".$lng->getTrn('matches/report/pts')."</td>\n";
                echo "<td><input type='text' name='tcas$N' value='".($m->{"tcas$N"})."' size='1' maxlength='2' $DIS></td>\n";
                echo "<td><input type='text' name='fame$N' value='".($m->{"fame$N"})."' size='1' maxlength='2' $DIS></td>\n";
                echo "<td><input type='text' name='tv$N' value='".($m->is_played ? $m->{"tv$N"}/1000 : ${"team$N"}->value/1000)."' size='4' maxlength='10' $DIS>k</td>\n";
                echo "</tr>\n";
            }
            ?>
        </table>

        <?php
        foreach (array(1 => $team1, 2 => $team2) as $id => $t) {
            ?>
            <table class='common'>
            <tr><td class='seperator' colspan='13'></td></tr>
            <tr class='commonhead'><td colspan='13'>
                <b><a href="<?php echo urlcompile(T_URL_PROFILE,T_OBJ_TEAM,$t->team_id,false,false);?>"><?php echo $t->name;?></a> <?php echo $lng->getTrn('matches/report/report');?></b>
            </td></tr>
            <tr><td class='seperator' colspan='13'></td></tr>
            <?php
            echo "<tr>\n";
            foreach (array_values(array_merge(self::$fields_rel, self::$fields_ach, self::$fields_inj)) as $f) {
                echo "<td><i>$f</i></td>\n";
            }
            echo "</tr>\n";
            
            foreach ($t->getPlayers() as $p) {

                if (!self::player_validation($p, $m))
                    continue;
            
                // Fetch player data from match
                $status = $p->getStatus($m->match_id);
                $mdat   = $p->getMatchData($m->match_id);

                // Print player row
                if ($p->is_journeyman) {$bgcolor = COLOR_HTML_JOURNEY;}
                elseif ($status == MNG) {$bgcolor = COLOR_HTML_MNG;}
                else {$bgcolor = false;}
                self::_print_player_row($p->player_id, $p->name, $p->nr, $p->position.(($status == MNG) ? '&nbsp;[MNG]' : ''),$bgcolor, $mdat, $DIS || ($status == MNG));
            }
            // Add raised zombies
#            global $racesHasNecromancer;
#            if (in_array($t->f_race_id, $racesHasNecromancer)) {
#                echo "<tr><td colspan='13'>Raised zombie? <input type='checkbox' name='t${id}zombie' value='1'></td></tr>\n";
#                self::_print_player_row("t${id}zombie", 'Raised zombie', '', 'Zombie', false, array('USE $mdat STRUCT HERE'), $DIS);
#            }
            ?>
            </table>

            <table style='border-spacing: 10px;'>
                <tr><td align="left" valign="top">
                    <b>Star Players</b>: 
                    <input type='button' id="addStarsBtn_<?php echo $id;?>" value="<?php echo $lng->getTrn('common/add');?>" 
                    onClick="stars = document.getElementById('stars_<?php echo $id;?>'); addStarMerc(<?php echo $id;?>, stars.options[stars.selectedIndex].value);" <?php echo $DIS; ?>>
                    <select id="stars_<?php echo $id;?>" <?php echo $DIS; ?>>
                        <?php
                        foreach ($stars as $s => $d) {
                            echo "<option ".((in_array($t->f_race_id, $d['races'])) ? 'style="background-color: '.COLOR_HTML_READY.';"' : '')." value='$d[id]'>$s</option>\n";
                        }
                        ?>
                    </select>
                </td></tr>
                <tr><td align="left" valign="top">
                    <b>Mercenaries</b>: <input type='button' id="addMercsBtn_<?php echo $id;?>" value="<?php echo $lng->getTrn('common/add');?>" onClick="addStarMerc(<?php echo "$id, ".ID_MERCS;?>);" <?php echo $DIS; ?>>
                </td></tr>
            </table>
            
            <table class='common' id='<?php echo "starsmercs_$id";?>'>
            </table>
            <?php
        }
        ?>
        <table class='common'>
            <tr><td class='seperator' colspan='13'></td></tr>
            <tr class='commonhead'><td colspan='13'><b><?php echo $lng->getTrn('matches/report/summary');?></b></td></tr>
            <tr><td colspan='13'><textarea name='summary' rows='10' cols='100' <?php echo $DIS . ">" . $m->comment; ?></textarea></td></tr>
        </table>
        <br><center><input type="submit" name='button' value="<?php echo $lng->getTrn('common/save');?>" <?php echo $DIS; ?>></center>
    </form>
    <br><br>
    <?php
    $CDIS = (!is_object($coach)) ? 'DISABLED' : '';
    ?>
    <table class="common">
        <tr class='commonhead'>
            <td colspan='13'><b><a href="javascript:void(0)" onclick="obj=document.getElementById('msmrc'); if (obj.style.display != 'none'){obj.style.display='none'}else{obj.style.display='block'};">[+/-]</a> <?php echo $lng->getTrn('matches/report/comments');?></b></td>
        </tr>
        <tr><td class='seperator'></td></tr>
        <tr><td><div id="msmrc">
            <?php echo $lng->getTrn('matches/report/existCmt');?>: <?php if (!$m->hasComments()) echo '<i>'.$lng->getTrn('common/none').'</i>';?><br><br>
            <?php
            foreach ($m->getComments() as $c) {
                echo "Posted $c->date by <b>$c->sname</b> 
                    <form method='POST' name='cmt$c->cid' style='display:inline; margin:0px;'>
                    <input type='hidden' name='type' value='cmtdel'>
                    <input type='hidden' name='cid' value='$c->cid'>
                    <a href='javascript:void(0);' onClick='document.cmt$c->cid.submit();'>".$lng->getTrn('common/delete')."</a>
                    </form>
                    :<br>".$c->txt."<br><br>\n";
            }
            ?>
        </div></td></tr>
        <tr><td>
            <form method="POST">
            <?php echo $lng->getTrn('matches/report/newCmt');?>:<br>
            <textarea name="msmrc" rows='5' cols='100' <?php echo $CDIS;?>><?php echo $lng->getTrn('common/nobody');?></textarea>
            <br>
            <input type="submit" value="<?php echo $lng->getTrn('common/submit');?>" name="new_msmrc" <?php echo $CDIS;?>>
            </form>
        </td></tr>
    </table>
    <script language='JavaScript' type='text/javascript'>
        document.getElementById('msmrc').style.display = 'none';
    </script>
    <?php
    
    /* 
        Now, we call javascript routine(s) to fill out stars and mercs rows, if such entries exist in database. 
    */
    
    $i = 0; // Counter. Used to pass PHP-data to Javascript.
    foreach (array(1 => $team1->team_id, 2 => $team2->team_id) as $id => $t) {
        foreach (Star::getStars(STATS_TEAM, $t, STATS_MATCH, $m->match_id) as $s) {
            echo "<script language='JavaScript' type='text/javascript'>\n";
            echo "var mdat$i = [];\n";
            $mdat = $s->getStats(T_NODE_MATCH,$m->match_id);
            foreach (array_keys(self::$fields_ach) as $f) {
                echo "mdat${i}['$f'] = ".$mdat[$f].";\n";
            }
            echo "existingStarMerc($id, $s->star_id, mdat$i);\n";
            echo "</script>\n";
            $i++;
        }
        
        foreach (Mercenary::getMercsHiredByTeam($t, $m->match_id) as $merc) {
            echo "<script language='JavaScript' type='text/javascript'>\n";
            echo "var mdat$i = [];\n";
            foreach (array_merge(array_keys(self::$fields_ach), array('skills')) as $f) {
                echo "mdat${i}['$f'] = ".$merc->$f.";\n";
            }
            echo "existingStarMerc($id, ".ID_MERCS.", mdat$i);\n";
            echo "</script>\n";
            $i++;
        }
    }
}


protected static function _print_player_row($FS, $name, $nr, $pos, $bgcolor, $mdat, $DISABLE) {

    $DIS = ($DISABLE) ? 'DISABLED' : '';
    echo "<tr".(($bgcolor) ? " style='background-color: $bgcolor;'" : '').">\n";
    echo "<td>$nr</td>\n";
    echo "<td>$name</td>\n";
    echo "<td>$pos</td>\n";
    echo "<td><input type='checkbox' " . ($mdat['mvp'] ? 'CHECKED ' : '')." $DIS name='mvp_$FS'></td>\n";
    foreach (array_diff(array_keys(self::$fields_ach), array('mvp')) as $f) {
        echo "<td><input $DIS type='text' onChange='numError(this);' size='1' maxlength='2' name='${f}_$FS' value='".$mdat[$f]."'></td>\n";
    }

    global $STATUS_TRANS;
    $STATUS_TRANS_AGN = array_diff_key($STATUS_TRANS, array(MNG => null, DEAD => null));
    foreach (array_combine(array_keys(self::$fields_inj), array($STATUS_TRANS,$STATUS_TRANS_AGN,$STATUS_TRANS_AGN)) as $f => $opts) {
        echo "<td><select name='${f}_$FS' $DIS>";
        foreach ($opts as $status => $name) {
            echo "<option value='$status' ".(($mdat[$f] == $status) ? 'SELECTED' : '').">$name</option>";
        }
        echo "</select></td>\n";
    }
    echo "</tr>\n";
}

private static function player_validation($p, $m) {

    // NOTE: we allow MNG players!

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
        if ($p->getStatus($m->match_id) == DEAD)
            return false;
    }
    // New match?
    else {
    
        if ($p->is_dead || $p->is_sold)
            return false;
    }
    
    return true;
}

public static function report_ES($mid, $DIS) 
{
    global $lng, $ES_fields;
    $ES_grps = array();
    foreach ($ES_fields as $f) {
        if (!in_array($f['group'], $ES_grps)) {
            $ES_grps[] = $f['group'];
        }
    }
    $players = self::report_ES_loadPlayers($mid);
    
    // Update entries if requested.
    if (!$DIS && isset($_POST['ES_submitted'])) {
        $query = "SELECT tour_id AS 'trid', did, f_lid AS 'lid' FROM matches, tours, divisions WHERE match_id = $mid AND f_tour_id = tour_id AND f_did = did";
        $result = mysql_query($query);
        $NR = mysql_fetch_assoc($result); # Node Relations.
        $m = new Match($mid);
        global $p; # Dirty trick to make $p accessible within create_function() below.
        $status = true;
        foreach ($players as $teamPlayers) {
        foreach ($teamPlayers as $p) {
            $status &= $m->ESentry(
                array(
                    'f_pid' => $p['pid'], 'f_tid' => $p['f_tid'], 'f_cid' => $p['f_cid'], 'f_rid' => $p['f_rid'], 
                    'f_mid' => $mid, 'f_trid' => $NR['trid'], 'f_did' => $NR['did'], 'f_lid' => $NR['lid']
                ),
                array_combine(array_keys($ES_fields), array_map(create_function('$f', 'global $p; return (int) $_POST["${f}_$p[pid]"];'), array_keys($ES_fields)))
            );
        }
        }
        status($status);
        $players = self::report_ES_loadPlayers($mid); # Reload!
    }
    
    // Create form
    title('ES submission');
    echo "<center><a href='index.php?section=matches&amp;type=report&amp;mid=$mid'>".$lng->getTrn('common/back')."</a></center>\n";
    HTMLOUT::helpBox('<b>Field explanations</b><br><table>'.implode("\n", array_map(create_function('$f,$def', 'return "<tr><td>$f</td><td>$def[desc]</td></tr>";'), array_keys($ES_fields), array_values($ES_fields))).'</table>', $lng->getTrn('common/needhelp'));
    echo "<form method='POST'>\n";
    foreach ($players as $teamPlayers) {
        echo "<br>\n";
        echo "<table style='font-size: small;'>\n"; 
        $COLSPAN = count($teamPlayers)+1; # +1 for field desc.
        $tid = $teamPlayers[0]['f_tid'];
        echo "<tr><td colspan='$COLSPAN'><b><a name='thead$tid'>".get_alt_col('teams', 'team_id', $tid, 'name')."</a></b></td></tr>";
        echo "<tr><td colspan='$COLSPAN'>Player number references:</td></tr>";
        echo implode('', array_map(create_function('$p', 'return "<tr><td colspan=\''.$COLSPAN.'\'>#$p[nr] $p[name]</td></tr>";'), $teamPlayers));
        echo "<tr><td colspan='$COLSPAN'>GOTO anchor ".implode(', ', array_map(create_function('$anc', 'return "<a href=\'#'.$tid.'$anc\'>$anc</a>";'), $ES_grps))."</td></tr>";
        $grp = null;
        foreach ($ES_fields as $f => $def) {
            if ($def['group'] != $grp) {
                $grp = $def['group'];
                echo "<tr><td colspan='$COLSPAN'>&nbsp;</td></tr>";
                echo "<tr style='font-style: italic;'><td><a name='$tid$grp'>$grp</a>&nbsp;|&nbsp;<a href='#thead$tid'>GOTO team head</a></td>".implode('', array_map(create_function('$p', 'return "<td>#$p[nr]</td>";'), $teamPlayers))."</tr>";
                echo "<tr><td colspan='$COLSPAN'><hr></td></tr>";
            }
            echo "<tr><td>$f</td>".implode('', array_map(
                create_function('$p', 'return "<td><input '.(($DIS) ? 'DISABLED' : '').' size=\'2\' maxlength=\'4\' name=\''.$f.'_$p[pid]\' value=\'".(($p[\''.$f.'\']) ? (int) $p[\''.$f.'\'] : 0)."\'></td>";'), $teamPlayers
            ))."</tr>\n";
        }
        echo "</table>\n";
    }
    echo "<br><br><input type='submit' name='submit' value='".$lng->getTrn('common/submit')."'>\n";
    echo "<input type='hidden' name='ES_submitted' value='1'>\n";
    echo "</form>\n";
}

protected static function report_ES_loadPlayers($mid) 
{
    global $ES_fields;
    $query = "SELECT 
            players.player_id AS 'pid', players.owned_by_team_id AS 'f_tid', players.f_cid AS 'f_cid', players.f_rid AS 'f_rid',
            players.name AS 'name', players.nr AS 'nr',
            ".implode(',', array_keys($ES_fields))." 
        FROM matches, match_data, players LEFT JOIN match_data_es ON (match_data_es.f_mid = $mid AND players.player_id = match_data_es.f_pid)
        WHERE 
            matches.match_id = $mid AND matches.match_id = match_data.f_match_id AND match_data.f_player_id = players.player_id AND (owned_by_team_id = team1_id OR owned_by_team_id = team2_id)
        ORDER BY f_tid ASC, nr ASC";
#    echo $query;
    $result = mysql_query($query);
    $players = array();
    while ($p = mysql_fetch_assoc($result)) {
        $players[$p['f_tid']][] = $p;
    }
    return $players;
}

}

?>