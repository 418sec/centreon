<?php
/*
 * Copyright 2005-2015 CENTREON
 * Centreon is developped by : Julien Mathis and Romain Le Merlus under
 * GPL Licence 2.0.
 *
 * This program is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free Software
 * Foundation ; either version 2 of the License.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
 * PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program; if not, see <http://www.gnu.org/licenses>.
 *
 * Linking this program statically or dynamically with other modules is making a
 * combined work based on this program. Thus, the terms and conditions of the GNU
 * General Public License cover the whole combination.
 *
 * As a special exception, the copyright holders of this program give CENTREON
 * permission to link this program with independent modules to produce an executable,
 * regardless of the license terms of these independent modules, and to copy and
 * distribute the resulting executable under terms of CENTREON choice, provided that
 * CENTREON also meet, for each linked independent module, the terms  and conditions
 * of the license of that module. An independent module is a module which is not
 * derived from this program. If you modify this program, you may extend this
 * exception to your version of the program, but you are not obliged to do so. If you
 * do not wish to do so, delete this exception statement from your version.
 *
 * For more information : contact@centreon.com
 *
 */

namespace CentreonAdministration\Repository;

use Centreon\Internal\Exception;
use Centreon\Internal\Di;
use CentreonAdministration\Models\Tag;
use CentreonConfiguration\Repository\HostRepository; 
use CentreonConfiguration\Repository\ServiceRepository; 

/**
 * Repository tags
 *
 * @author Maximilien Bersoult <mbersoult@centreon.com>
 * @version 3.0.0
 * @package Centreon
 * @package CentreonAdministration
 */
class TagsRepository extends Repository
{
    /**
     * The list of resource who can have a tag
     * @var array
     */
    private static $resourceType = array(
        'host',
        //'hosttemplate',
        'service',
        //'servicetemplate',
        'ba',
        'contact'
    );
    
    private static $aConvertResource = array (
        'hosttemplate' => 'host', 
        'servicetemplate' => 'service',
        'businessactivity' => 'ba'
    );
    
    public static $objectClass = '\CentreonAdministration\Models\Tag';
    
    /**
     *
     * @var type 
     */
    public static $unicityFields = array(
        'fields' => array(
            'tag' => 'cfg_tags, tag_id, tagname'
        ),
    );
    
    public static $attributesMap = array(
        
    );
    
    protected static function convertResource($sResource)
    {
        if (array_key_exists($sResource, self::$aConvertResource)) {
            $sResource = self::$aConvertResource[$sResource];
        }
        return $sResource;
    }

    /**
     * Add a tag to a resource
     *
     */
    public static function add($tagName, $resourceName, $resourceId, $bGlobal = 0, $iIdTemplate =  '')
    {
        $resourceName = self::convertResource($resourceName);
        if (!in_array($resourceName, static::$resourceType)) {
            throw new Exception("This resource type does not support tags.");
        }
        if ($bGlobal == 0) {
            $userId = $_SESSION['user']->getId();
        } else {
            $userId = NULL;
        }   

        $dbconn = Di::getDefault()->get('db_centreon');
        /* Get or create a tagname */
        try {
            $tagId = static::getTagId($tagName, $bGlobal);
        } catch (Exception $e) {
            $tagId = Tag::insert(
                array(
                    'user_id' => $userId,
                    'tagname' => $tagName
                )
            );
        }
           
        /* Insert relation tag */
        if (!self::isLink($resourceName, $resourceId, $tagId)) {
            self::associateTagWithResource($resourceName, $tagId, $resourceId, $iIdTemplate);
        }
        return $tagId;
    }

    /**
     * Delete the relation between a tag and a resource
     *
     */
    public static function delete($tagId, $resourceName, $resourceId)
    {
        $resourceName = self::convertResource($resourceName);
        
        if (!in_array($resourceName, static::$resourceType)) {
            throw new Exception("This resource type does not support tags.");
        }
        $dbconn = Di::getDefault()->get('db_centreon');
        
        if (!is_numeric($tagId)) {
            $tagId = static::getTagId($tagId, 1);
        }
        /* Get current user id */
        $query = "DELETE FROM cfg_tags_" . $resourceName . "s WHERE
            tag_id = :tag_id
            AND resource_id = :resource_id";
        
        $stmt = $dbconn->prepare($query);
        $stmt->bindParam(':tag_id', $tagId, \PDO::PARAM_INT);
        $stmt->bindParam(':resource_id', $resourceId, \PDO::PARAM_INT);
        $stmt->execute();
        /* Get if tag is used */
        if (!static::isUsed($tagId)) {
            Tag::delete($tagId);
        }   
    }

    /**
     * Return the list of tags for a resource
     * 
     * @param type $resourceName
     * @param int $resourceId
     * @param int $bGlobaux
     * @param int $bWithHerited 
     * @return array
     * @throws Exception
     */
    public static function getList($resourceName, $resourceId, $bGlobaux = 0, $bWithHerited = 1, $bForceId = 0)
    {
        $resourceName = self::convertResource($resourceName);
        if (!in_array($resourceName, static::$resourceType)) {
            throw new Exception("This resource type does not support tags.");
        }

        $dbconn = Di::getDefault()->get('db_centreon');        

        $query = 'SELECT t.tag_id, t.tagname, user_id, template_id'
            . ' FROM cfg_tags t, cfg_tags_' . $resourceName . 's r'
            . ' WHERE t.tag_id = r.tag_id AND ';
         
        if ($bGlobaux == 0) {//only tag for user
            $query .= " t.user_id = :user_id";
        } elseif ($bGlobaux == 1) {//only global tag
            $query .= " t.user_id is null ";
        } else {//tag user + global tag
            $query .= " (t.user_id is null or t.user_id = :user_id)";
        }
         
        if ($resourceId > 0) {
            $query .= " AND r.resource_id = :resource_id";
        }
        if ($bWithHerited == 0) {
            $query .= " AND (template_id IS NULL OR template_id = 0) ";
        }
        
        $query .= " ORDER BY tagname ASC";
      
        $stmt = $dbconn->prepare($query);
        
        if ($resourceId > 0) {
            $stmt->bindParam(':resource_id', $resourceId, \PDO::PARAM_INT);
        }
        if ($bGlobaux == 0 || $bGlobaux == 2) {
            $userId = $_SESSION['user']->getId();
            $stmt->bindParam(':user_id', $userId, \PDO::PARAM_INT);
        }
        $stmt->execute();
        $tags = array();
        
        while ($row = $stmt->fetch()) {
            if (($bGlobaux == 0) || ($bForceId == 1)) {
                $sField = $row['tag_id'];
            } else {
                $sField = $row['tagname'];
            }
            $tags[] = array('id' => $sField, 'text' => $row['tagname'], 'user_id' => $row['user_id'], 'template_id' => $row['template_id']);
        }
        return $tags;
    }

    /**
     * Get global tag list
     *
     * @param string $resourceName
     * @return array
     */
    public static function getGlobalList($resourceName = null)
    {
        $addFilter = array();
        $tablesString = 'cfg_tags';
        if (isset($resourceName)) {
            $addFilter['join'] = array(
                'cfg_tags_' . $resourceName . 's.tag_id = cfg_tags.tag_id'
            );
            $tablesString = 'cfg_tags, cfg_tags_' . $resourceName . 's';
        }
      
        $tagList = Tag::getList('tag_id, tagname', -1, 0, null, 'ASC', array(), 'OR', $tablesString, null, $addFilter);
        $tags = array();
        foreach ($tagList as $tag) {
            $tags[] = array('id' => $tag['tag_id'], 'text' => $tag['tagname']);
        }
        return $tags;
    }

    /**
     * Get the tag id
     *
     * @param string $tagName The tag
     * @param int $bGlobal
     * @param int $bDisplayError
     * @return int
     */
    public static function getTagId($tagName, $bGlobal = 0, $bDisplayError = 1)
    {
        $tagName = trim($tagName);
        if (empty($tagName)) {
            return;
        }
        
        $dbconn = Di::getDefault()->get('db_centreon'); 
        $query = "SELECT tag_id FROM cfg_tags WHERE tagname = '".$tagName."'";
        if ($bGlobal == 0) {
           $query .= " AND user_id = :user_id";
           
        }
        
        $query .= " LIMIT 1";
        $stmt = $dbconn->prepare($query);
        
        if ($bGlobal == 0) {
            $stmt->bindParam(':user_id', $_SESSION['user']->getId(), \PDO::PARAM_INT);
        }
        
        $stmt->execute();
        $tag = $stmt->fetchAll(\PDO::FETCH_ASSOC);
  
        if (count($tag) === 0 && $bDisplayError == 1) {
            throw new Exception("The tag is not found in this object");
        }
        
        if (isset($tag[0]['tag_id'])) {
            $sRep = $tag[0]['tag_id'];
        } else {
            $sRep = "";
        }
        return $sRep;
    }

    /**
     * Return the list of resource who can have 
     *
     * @return array
     */
    public static function getListResource()
    {
        return static::$resourceType;
    }

    /**
     * Return if a tag is used
     *
     * @param int $tagId The tag id
     * @return bool
     */
    protected static function isUsed($tagId)
    {
        $dbconn = Di::getDefault()->get('db_centreon');
        foreach (static::$resourceType as $resource) {
            $query = "SELECT COUNT(*) as nb FROM cfg_tags_" . $resource . "s WHERE tag_id = :tag_id";
            $stmt = $dbconn->prepare($query);
            $stmt->bindParam(':tag_id', $tagId, \PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch();
            if ($row['nb'] > 0) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * 
     * @param type $resourceName
     * @param type $objectId
     * @param type $submittedValues
     * @param type $iIdTemplate
     * @param int  $sGlobal
     * @throws Exception
     */
    public static function saveTagsForResource($resourceName, $objectId, $submittedValues, $iIdTemplate = '', $bNotDelete = false, $sGlobal)
    {
        $resourceName = self::convertResource($resourceName);
        if (!in_array($resourceName, static::$resourceType)) {
            throw new Exception("This resource type does not support tags.");
        }

        $dbconn = Di::getDefault()->get('db_centreon');

        if (!$bNotDelete) {
            $sQuery = "DELETE FROM cfg_tags_" . $resourceName . "s WHERE resource_id = :resource_id "
                . "AND tag_id in (select tag_id from cfg_tags where user_id is null)";
                       
            $stmt = $dbconn->prepare($sQuery);
            $stmt->bindParam(':resource_id', $objectId, \PDO::PARAM_INT);
            $stmt->execute();
        }
        
        foreach ($submittedValues as $s => $tagName) {
            if (!is_numeric($tagName)) {
                self::add($tagName, $resourceName, $objectId, $sGlobal, $iIdTemplate);
            } else {
                self::associateTagWithResource($resourceName, $tagName, $objectId, $iIdTemplate);
            }
        }
      
    }
    public static function getTag($resourceType, $resourceId, $tagId, $tagName, $iUserId, $iTempalteId = '')
    {
        if ($iUserId != '') {
            $sClass = 'tag';
            $sDivRemove = '<div class="remove"><a href="#">&times;</a></div>';
        } else {
            if (!empty($iTempalteId)) {
                $sClass = 'tagGlobalNotDelete';
            } else {
                $sClass = 'tagGlobal';
            }
            $sDivRemove = '';
        }
        $html = '<div class="'.$sClass.'" data-resourceid="' . $resourceId . '" data-resourcetype="'
            . $resourceType .'" data-tagid="' . $tagId . '">
            <div class="tagname">' . $tagName . '</div>
            '.$sDivRemove.'
        </div> ';
        return $html;
    }

    public static function getAddTag($resourceType, $resourceId)
    {
        $html = '<div class="tag addtag" data-resourceid="' . $resourceId . '" data-resourcetype="'
            . $resourceType .'">
            <div class="title"><input type="text" style="width: 0;" maxlength="30"></div>
            <div class="remove noborder"><a href="#">+</a></div>
        </div>';
        return $html;
    }
    
    
    /**
     * Return the list of tags for all resources
     * @param string $sSearch String to search
     * @param int $sType Type of search
     * @return array
     * @throws Exception
     */
    public static function getAllList($sSearch, $sType = 1)
    {
        $dbconn = Di::getDefault()->get('db_centreon');
        $userId = $_SESSION['user']->getId();
        
        $sSearch = trim($sSearch);
        if (!empty($sSearch)) {
            if ($sType == 1) {
                $query = "SELECT tag_id, tagname FROM cfg_tags where user_id is null AND tagname LIKE :search  ORDER BY tagname ASC ";   
            } else {
                $query = "SELECT tag_id, tagname FROM cfg_tags where user_id = :user_id AND tagname LIKE :search ORDER BY tagname ASC ";   
            }
        } else {
            if ($sType == 1) {
                $query = "SELECT tag_id, tagname FROM cfg_tags where user_id is null ORDER BY tagname ASC ";   
            } else {
                $query = "SELECT tag_id, tagname FROM cfg_tags where user_id = :user_id ORDER BY tagname ASC ";   
            }
        }

        $stmt = $dbconn->prepare($query);
        
        if (!empty($sSearch)) {
            $sSearch  = "%".$sSearch."%";
            if ($sType == 1) {
                $stmt->bindParam(':search', $sSearch, \PDO::PARAM_STR);
            } else {
                $stmt->bindParam(':user_id', $userId, \PDO::PARAM_INT);
                $stmt->bindParam(':search', $sSearch, \PDO::PARAM_STR);
            }
        } else {
            if ($sType != 1) {
                $stmt->bindParam(':user_id', $userId, \PDO::PARAM_INT);
            }
        }

        $stmt->execute();
        $tags = array();
        
        while ($row = $stmt->fetch()) {
            $tags[] = array('id' => $row['tag_id'], 'text' => $row['tagname']);
        }
        return $tags;
    }
    
    
    /**
     * Delete the global tags
     * @param type $aDatas
     * @return type
     */
    public static function deleteGlobal($aDatas)
    {

        $dbconn = Di::getDefault()->get('db_centreon');
        
        if (!is_array($aDatas) || count($aDatas) == 0) {
            return;
        }

        $sQueryDelete = "DELETE FROM cfg_tags WHERE tag_id = :id";
        $oSmtDelete = $dbconn->prepare($sQueryDelete);
        foreach ($aDatas as $id) {
            foreach (static::$resourceType as $resource) {
                $sQuery = "DELETE FROM cfg_tags_" . $resource . "s WHERE tag_id = :id";
                $oSmt = $dbconn->prepare($sQuery);
                $oSmt->bindParam(':id', $id, \PDO::PARAM_INT);
                $oSmt->execute();
            }
            
            $oSmtDelete->bindParam(':id', $id, \PDO::PARAM_INT);
            $oSmtDelete->execute(); 
        }
    }
    /**
     * 
     * @param array $givenParameters
     * @return type
     */
    public static function update($givenParameters, $origin = "", $route = "", $validate = true, $validateMandatory = true)
    {
        $dbconn = Di::getDefault()->get('db_centreon');
        
        if ($validate) {
            self::validateForm($givenParameters, $origin, $route, $validateMandatory);
        }
        
        $tagId = $givenParameters['object_id'];
        $tagName = $givenParameters['tagname'];
        
        if (empty($tagId)|| empty($tagName)) {
            return;
        }
        
        $query = "UPDATE cfg_tags SET tagname = :tagname WHERE tag_id = :tag_id";

        $stmt = $dbconn->prepare($query);
        $stmt->bindParam(':tag_id', $tagId, \PDO::PARAM_INT);
        $stmt->bindParam(':tagname', $tagName, \PDO::PARAM_STR);
        $stmt->execute();

    }
    
    /**
     * Get the tag id
     * @param string $tagName The tag
     * @return int
     */
    public static function isExist($tagName)
    {
        if (empty($tagName)) {
            return;
        }
        
        $dbconn = Di::getDefault()->get('db_centreon');
        
        if (empty($tagName)) {
            return;
        }
        
        $query = "SELECT tag_id FROM cfg_tags WHERE tagname = :tagname";

        $stmt = $dbconn->prepare($query);
        $stmt->bindParam(':tagname', $tagName, \PDO::PARAM_STR);
        $tag = $stmt->execute();
        
        $tags = array();
        
        while ($row = $stmt->fetch()) {
            $tags[] = array('id' => $row['tag_id']);
        }
        
        if (isset($tags[0]['id'])) {
            $iReturn = $tags[0]['id'];
        } else {
            $iReturn = -1;
        }
        return $iReturn;
        
    }
    /**
     * 
     * @param type $resourceName
     * @param type $iTagId
     * @param type $iResourceId
     * @param int $iIdTemplate Description
     */
    
    public static function associateTagWithResource($resourceName, $iTagId, $iResourceId, $iIdTemplate = '')
    {       
        $resourceName = self::convertResource($resourceName);
        $dbconn = Di::getDefault()->get('db_centreon');
        try {
            $query = "INSERT INTO cfg_tags_" . $resourceName . "s (tag_id, resource_id, template_id)
                        VALUES (:tag_id, :resource_id, :TemplateId)";

            $stmt = $dbconn->prepare($query);
            $stmt->bindParam(':tag_id', $iTagId, \PDO::PARAM_INT);
            $stmt->bindParam(':resource_id', $iResourceId, \PDO::PARAM_INT);
            $stmt->bindParam(':TemplateId', $iIdTemplate);
            $stmt->execute();
            $bStatus = true;
        } catch (\Exception $e) {
            $bStatus = false;
        }
        
        return $bStatus;
    }
    
    /**
     * Return the list of tags for a resource
     * 
     * @param type $resourceName
     * @param int $resourceId
     * @return array
     * @throws Exception
     */
    public static function getListId($resourceName, $resourceId)
    {
        $resourceName = self::convertResource($resourceName);
        if (!in_array($resourceName, static::$resourceType)) {
            throw new Exception("This resource type does not support tags.");
        }
        if (empty($resourceId)) {
            return array();
        }

        $dbconn = Di::getDefault()->get('db_centreon');
        
        $query = "SELECT t.tag_id, t.tagname, template_id ,user_id
                FROM cfg_tags t LEFT JOIN cfg_tags_" . $resourceName . "s r ON t.tag_id = r.tag_id
                WHERE t.user_id is null AND r.resource_id = :resource_id";
        
        $stmt = $dbconn->prepare($query);
        $stmt->bindParam(':resource_id', $resourceId, \PDO::PARAM_INT);
        $stmt->execute();
        $tags = array();
               
        while ($row = $stmt->fetch()) {
            $tags[] = array('id' => $row['tag_id'], 'text' => $row['tagname'], 'tpl' => $row['template_id']);
        }
        return $tags;
    }
    
    /**
     * 
     * @param type $resourceName
     * @param type $resourceId
     * @param type $tagId
     * @return boolean
     */
    public static function isLink($resourceName, $resourceId, $tagId)
    {       
        $resourceName = self::convertResource($resourceName);
        if (!in_array($resourceName, static::$resourceType)) {
            throw new Exception("This resource type does not support tags.");
        }
        
        $dbconn = Di::getDefault()->get('db_centreon');
        foreach (static::$resourceType as $resource) {
            $query = "SELECT COUNT(*) as nb FROM cfg_tags_" . $resourceName . "s WHERE tag_id = :tag_id AND resource_id = :resource_id";
            $stmt = $dbconn->prepare($query);
            $stmt->bindParam(':tag_id', $tagId, \PDO::PARAM_INT);
            $stmt->bindParam(':resource_id', $resourceId, \PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch();
            if ($row['nb'] > 0) {
                return true;
            }
        }
        return false;
    }
    
    /**
     * 
     * @param type $resourceName
     * @param type $resourceId
     * @return boolean
     */
    public static function deleteTagsForResource($resourceName, $resourceId, $isTempalte  = 0)
    {
        $dbconn = Di::getDefault()->get('db_centreon');
        $resourceName = self::convertResource($resourceName);
        
        $sQuery = "DELETE FROM cfg_tags_" . $resourceName . "s WHERE resource_id = :resource_id "
                . "AND tag_id in (select tag_id from cfg_tags where user_id is null)";
        
        if ($isTempalte == 1) {
           $sQuery .= " AND template_id > 0 "; 
        }
        
        $stmt = $dbconn->prepare($sQuery);
        $stmt->bindParam(':resource_id', $resourceId, \PDO::PARAM_INT);
        $stmt->execute();
    }
    
    
    /**
     * Return the list of tags for a template
     * 
     * @param type $resourceName
     * @param int $iTemplateId
     * @return array
     * @throws Exception
     */
    public static function getListByTplId($resourceName, $iTemplateId)
    {
        $resourceName = self::convertResource($resourceName);
        if (!in_array($resourceName, static::$resourceType)) {
            throw new Exception("This resource type does not support tags.");
        }
        if (empty($iTemplateId)) {
            return array();
        }

        $dbconn = Di::getDefault()->get('db_centreon');
        
        $query = "SELECT t.tag_id, t.tagname, template_id, user_id, resource_id
                FROM cfg_tags t LEFT JOIN cfg_tags_" . $resourceName . "s r ON t.tag_id = r.tag_id
                WHERE t.user_id is null AND template_id = :template_id";
                
        $stmt = $dbconn->prepare($query);
        $stmt->bindParam(':template_id', $iTemplateId, \PDO::PARAM_INT);
        $stmt->execute();
        $tags = array();
               
        while ($row = $stmt->fetch()) {
            $tags[] = array('tag_id' => $row['tag_id'], 'resource_id' => $row['resource_id'], 'tpl' => $row['template_id']);
        }
        return $tags;
    }
    
    /**
     * Get the list of inhereted tags
     * @param string $resourceName
     * @param int $resourceId
     * @return array
     */
    
    public static function getHeritedTags($resourceName, $resourceId)
    {
        $resourceName = self::convertResource($resourceName);
        if (!in_array($resourceName, static::$resourceType)) {
            throw new Exception("This resource type does not support tags.");
        }
        if (empty($resourceId)) {
            return array();
        }
        
        $aTagUsed = array();
        $aTags = array();
        
        if ($resourceName == 'host') {
            $templates = HostRepository::getTemplateChain($resourceId, array(), -1);
            foreach ($templates as $template) {
                $aTagsInHost = TagsRepository::getList('host', $template['id'], 1, 0);
                foreach ($aTagsInHost as $oTags) {
                    if (!in_array($oTags['id'], $aTagUsed)) {
                        $aTagUsed[] = $oTags['id'];
                        $aTags[] = $oTags['text'];
                    }
                }
            }
        } elseif ($resourceName == 'service') {
            $templates = ServiceRepository::getListTemplates($resourceId, array(), -1);
            foreach ($templates as $template) {
                $aTagsInSvc = TagsRepository::getList('service', $template, 1, 0);
                foreach ($aTagsInSvc as $oTags) {
                    if (!in_array($oTags['id'], $aTagUsed)) {
                        $aTagUsed[] = $oTags['id'];
                        $aTags[] = $oTags['text'];
                    }
                }
            }
        }

        return array('success' => true, 'values' => $aTags);
    }
}
