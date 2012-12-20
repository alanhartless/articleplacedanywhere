<?php
/**
 * @copyright    Copyright (C) 2008 Ian MacLennan. All rights reserved.
 * @copyright    Upgrade to J2.5.  Copyright 2012 HartlessByDesign, LLC.
 * @copyright	Portions Copyright (C) 2005 - 2008 Open Source Matters. All rights reserved.
 * @license   GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */


// no direct access
defined('_JEXEC') or die('Restricted access');

class modArticlePlacedAnywhereHelper
{
	static function renderItem(&$item, &$params)
	{
		$item->text 	= JHTML::_('content.prepare', $item->introtext);
		$item->groups 	= '';
		$item->readmore = (trim($item->fulltext) != '');
		$item->metadesc = '';
		$item->metakey 	= '';
		$item->access 	= '';
		$item->created 	= '';
		$item->modified = '';

		if (!$params->get('image')) {
			$item->text = preg_replace( '/<img[^>]*>/', '', $item->text );
		}

		require(JModuleHelper::getLayoutPath('mod_articleplacedanywhere', '_item'));
	}

	static function getItem(&$params)
	{
		$db 	= JFactory::getDBO();
		$id 	= (int) $params->get('id', 0);

		jimport('joomla.utilities.date');

		// query to get article
        $query = $db->getQuery(true);

        $query->select(
            'a.id, a.asset_id, a.title, a.alias, a.introtext, a.fulltext, ' .
            // If badcats is not null, this means that the article is inside an unpublished category
            // In this case, the state is set to 0 to indicate Unpublished (even if the article state is Published)
            'CASE WHEN badcats.id is null THEN a.state ELSE 0 END AS state, ' .
            'a.catid, a.created, a.created_by, a.created_by_alias, ' .
            // use created if modified is 0
            'CASE WHEN a.modified = 0 THEN a.created ELSE a.modified END as modified, ' .
            'a.modified_by, a.checked_out, a.checked_out_time, a.publish_up, a.publish_down, ' .
            'a.images, a.urls, a.attribs, a.version, a.ordering, ' .
            'a.metakey, a.metadesc, a.access, a.hits, a.metadata, a.featured, a.language, a.xreference'
        );
        $query->from('#__content AS a');

        // Join on category table.
        $query->select('c.title AS category_title, c.alias AS category_alias, c.access AS category_access');
        $query->join('LEFT', '#__categories AS c on c.id = a.catid');

        // Join on user table.
        $query->select('u.name AS author');
        $query->join('LEFT', '#__users AS u on u.id = a.created_by');

        // Join over the categories to get parent category titles
        $query->select('parent.title as parent_title, parent.id as parent_id, parent.path as parent_route, parent.alias as parent_alias');
        $query->join('LEFT', '#__categories as parent ON parent.id = c.parent_id');

        $query->where('a.id = ' . (int) $id);

        // Filter by start and end dates.
        $nullDate = $db->Quote($db->getNullDate());
        $date = JFactory::getDate();

        $nowDate = $db->Quote($date->toSql());

        $query->where('(a.publish_up = ' . $nullDate . ' OR a.publish_up <= ' . $nowDate . ')');
        $query->where('(a.publish_down = ' . $nullDate . ' OR a.publish_down >= ' . $nowDate . ')');
        $user 	= JFactory::getUser();
        $groups	= implode(',', $user->getAuthorisedViewLevels());
        $query->where('a.access IN ('.$groups.')');
        $query->where('a.state = 1');

        // Join to check for category published state in parent categories up the tree
        // If all categories are published, badcats.id will be null, and we just use the article state
        $subquery  = ' (SELECT cat.id as id FROM #__categories AS cat JOIN #__categories AS parent ';
        $subquery .= 'ON cat.lft BETWEEN parent.lft AND parent.rgt ';
        $subquery .= 'WHERE parent.extension = ' . $db->quote('com_content');
        $subquery .= ' AND parent.published <= 0 GROUP BY cat.id)';
        $query->join('LEFT OUTER', $subquery . ' AS badcats ON badcats.id = c.id');

        $db->setQuery($query);
        $row = $db->loadObject();

        $row->slug			= $row->alias ? ($row->id.':'.$row->alias) : $row->id;
        $row->catslug		= $row->category_alias ? ($row->catid.':'.$row->category_alias) : $row->catid;
        $row->parent_slug	= $row->category_alias ? ($row->parent_id.':'.$row->parent_alias) : $row->parent_id;

		return $row;
	}
}
