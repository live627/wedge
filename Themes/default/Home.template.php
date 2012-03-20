<?php
/**
 * Wedge
 *
 * Displays the custom homepage. Hack away!
 *
 * @package wedge
 * @copyright 2010-2011 Wedgeward, wedge.org
 * @license http://wedge.org/license/
 *
 * @version 0.1
 */

function template_main()
{
	global $user_info, $txt;

	echo '
	<we:cat>
		Welcome! Wedge is the next generation of free forum software.
	</we:cat>
	<div class="windowbg2 wrc">
		Built in PHP 5 upon the SMF platform by some of its top devs (see the <a href="/pub/faq/">FAQ</a> for more),
		it allows you to create and maintain <strong>message boards</strong> with a modern twist. Blogs, HTML 5, CSS 3, jQuery, object
		programming, UTF8, CSS pre-parsing, improved security, drafts, easier theming and modding, it\'s got it all.
		Read the full <a href="/pub/feats/">feature list</a>. With its dozens of new features, including the exclusive
		Aeva Media gallery system, it puts control back into your hands. <a href="/blog/">Stay tuned</a> for more&hellip;
	</div>';

	$n = isset($_REQUEST['n']) ? (int) $_REQUEST['n'] : 5;
	$next = $n < 50 ? ($n < 20 ? ($n < 10 ? 10 : 20) : 50) : 100;

	echo '
	<we:block class="windowbg" style="margin-top: 16px">
		<header>', $n == $next ? '' : '
			<a href="?n=' . $next . '"><div class="floatleft foldable" style="margin: 1px 4px 1px 1px"></div></a>', '
			<span class="floatright"><a href="<URL>?action=boards">', $txt['board_index'], '</a></span>
			Forums
		</header>
		<div style="margin: -.8em -.9em">
			<table class="homeposts w100 cs1 cp0">';

	loadSource('../SSI');
	$naoboards = ssi_recentTopics($n, null, null, 'naos', false, 0);

	$new_stuff = array();
	if (!$user_info['is_guest'])
		foreach ($naoboards as $post)
			if ($post['is_new'])
				$new_stuff[] = $post['topic'];

	if (count($new_stuff) > 0)
	{
		$nb_new = array();
		$request = wesql::query('
			SELECT COUNT(DISTINCT m.id_msg) AS co, m.id_topic
			FROM {db_prefix}messages AS m
				LEFT JOIN {db_prefix}log_topics AS lt ON (lt.id_topic = m.id_topic AND lt.id_member = {int:id_member})
				LEFT JOIN {db_prefix}log_mark_read AS lmr ON (lmr.id_board = m.id_board AND lmr.id_member = {int:id_member})
			WHERE m.id_topic IN ({array_int:new_stuff})
					AND (m.id_msg > IFNULL(lt.id_msg, IFNULL(lmr.id_msg, 0)))
			GROUP BY m.id_topic',
			array(
				'id_member' => $user_info['id'],
				'new_stuff' => $new_stuff
			)
		);
		while ($row = wesql::fetch_assoc($request))
			$nb_new[$row['id_topic']] = $row['co'];
		wesql::free_result($request);
	}
	unset($new_stuff, $row);

	foreach ($naoboards as $post)
	{
		$safe = strpos($post['board']['url'], '/pub') === false;
		$blo = strpos($post['board']['url'], '/blog') !== false;
		echo '
				<tr>
					<td class="windowbg latestp1">
						<div>', strftime('%d/%m %H:%M', $post['timestamp']), '<br>', $post['poster']['link'], '</div>
					</td>
					<td class="windowbg2 latestp2">
						', $post['board']['name'], ' &gt; ';

		if ($post['is_new'] && !$user_info['is_guest'])
			echo isset($nb_new[$post['topic']]) ? '<a href="' . $post['href'] . '" class="note">' . $nb_new[$post['topic']] . '</a> ' : '';

		echo '<a href="', $post['href'], $safe ? '" style="color: ' . ($blo ? '#a62' : 'green') : '', '">', $post['subject'], '</a>
					</td>
				</tr>';
		}

	echo '
			</table>
		</div>
	</we:block>';
}

function template_thoughts($limit = 18)
{
	global $txt, $user_info, $context;

	if (empty($context['thoughts']))
		return;

	$is_thought_page = isset($_GET['s']) && $_GET['s'] === 'thoughts';

	if (!$is_thought_page)
		echo '
		<we:cat style="margin-top: 16px">
			<div class="thought_icon"></div>
			', $txt['thoughts'], '... (<a href="<URL>?s=thoughts">', $txt['all_pages'], '</a>)
		</we:cat>';

	echo '
		<div class="tborder" style="margin: 5px 0 15px; padding: 2px; border: 1px solid #dcc; border-radius: 5px">
		<table class="w100 cp4 cs0 thought_list">';

	if ($is_thought_page)
		echo '
			<tr><td colspan="2" class="titlebg" style="padding: 4px">', $txt['pages'], ': ', $context['page_index'], '</td></tr>';

	if (!$user_info['is_guest'])
		echo '
			<tr id="new_thought">
				<td class="bc">{date}</td><td class="windowbg thought">{uname} &raquo; {text}</td>
			</tr>';

	foreach ($context['thoughts'] as $id => $thought)
	{
		$col = empty($col) ? 2 : '';
		echo '
			<tr class="windowbg', $col, '">
				<td class="bc', $col, '">', $thought['updated'], '</td>
				<td><div><a id="t', $id, '"></a><a href="<URL>?action=profile;u=', $thought['id_member'], '">',
				$thought['owner_name'], '</a> &raquo; ', $thought['text'], '</div></td>
			</tr>';
	}

	if ($is_thought_page)
		echo '
			<tr><td colspan="2" class="titlebg" style="padding: 4px">', $txt['pages'], ': ', $context['page_index'], '</td></tr>';

	echo '
		</table>
		</div>';
}

// Only restore this if we decide to remove the board index...
function template_quickboard()
{
/*
	global $user_info;

	$can_view = array_intersect($user_info['groups'], array(1, 18, 20, 21));
	$is_team = array_intersect($user_info['groups'], array(1, 20, 21));

	echo '
	<section>
		<we:title>
			Quick Board List (<a href="http://wedge.org/do/boards/">full</a>)
		</we:title>
		<div class="padding">
			<b><a href="http://wedge.org/pub/">The Pub</a></b>
			<br />&nbsp;&nbsp;&nbsp;<a href="http://wedge.org/pub/faq/">FAQs</a>
			<br />&nbsp;&nbsp;&nbsp;<a href="http://wedge.org/pub/feats/">Features</a>
			<br />&nbsp;&nbsp;&nbsp;<a href="http://wedge.org/pub/plugins/">Plugins</a>
			<br />&nbsp;&nbsp;&nbsp;<a href="http://wedge.org/pub/off/">Off-topic</a>
			<br />&nbsp;&nbsp;&nbsp;<a href="http://wedge.org/pub/smf/">SMF</a>
			<br /><b><a href="http://wedge.org/blog/">The Blog</a></b>', !empty($can_view) ? '
			<br /><b><a href="http://wedge.org/up/">The Project</a></b>' . (!empty($is_team) ? '
			<br /><b><a href="http://wedge.org/team/">Team board</a></b>' : '') . '
			<br /><b><a href="http://wedge.org/code/">Feature discussion</a></b>
			<br />&nbsp;&nbsp;&nbsp;<a href="http://wedge.org/gfx/">Theme &amp; UI</a>
			<br />&nbsp;&nbsp;&nbsp;<a href="http://wedge.org/bzz/">Sleeping in light</a>
			<br />&nbsp;&nbsp;&nbsp;<a href="http://wedge.org/out/">Finished!</a>
			<br /><b><a href="http://wedge.org/off/">Off-topic</a></b>' : '', '
		</div>
	</section>';
*/
}

?>