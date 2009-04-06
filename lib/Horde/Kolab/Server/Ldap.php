<?php
/**
 * The driver for accessing objects stored in LDAP.
 *
 * PHP version 5
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */

/**
 * This class provides methods to deal with objects stored in
 * a LDAP db.
 *
 * Copyright 2008-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @category Kolab
 * @package  Kolab_Server
 * @author   Gunnar Wrobel <wrobel@pardus.de>
 * @license  http://www.fsf.org/copyleft/lgpl.html LGPL
 * @link     http://pear.horde.org/index.php?package=Kolab_Server
 */
class Horde_Kolab_Server_Ldap extends Horde_Kolab_Server
{
    /**
     * LDAP connection handle.
     *
     * @var Net_LDAP2
     */
    private $_ldap;

    /**
     * The configuration for connection to the LDAP server.
     *
     * @var array
     */
    private $_config;

    /**
     * Base DN of the LDAP server.
     *
     * @var string
     */
    private $_base_dn;

    /**
     * The LDAP schemas.
     *
     * @var Net_LDAP2_Schema
     */
    private $_schema;

    /**
     * A cache for object attribute definitions.
     *
     * @var array
     */
    static protected $attributes;

    /**
     * Construct a new Horde_Kolab_Server_ldap object.
     *
     * @param array $params Parameter array.
     */
    public function __construct($params = array())
    {
        if (!isset($params['charset'])) {
            $params['charset'] = 'UTF-8';
        }

        $base_config = array('host'           => 'localhost',
                             'port'           => 389,
                             'version'        => 3,
                             'starttls'       => false,
                             'uid'            => '',
                             'pass'           => '',
                             'basedn'         => '',
                             'charset'        => '',
                             'options'        => array(),
                             'auto_reconnect' => true);


        $config = array_merge($base_config, $params);

        $this->_base_dn = $config['basedn'];

        $config['binddn'] = $config['uid'];
        $config['bindpw'] = $config['pass'];

        $this->_config = $config;

        $this->connect();

        parent::__construct($params);
    }

    
    /**
     * Connect to the LDAP server.
     *
     * @return NULL.
     *
     * @throws Horde_Kolab_Server_Exception If the connection failed.
     */
    protected function connect()
    {
        $this->_ldap = Net_LDAP2::connect($this->_config);
        if (is_a($this->_ldap, 'PEAR_Error')) {
            throw new Horde_Kolab_Server_Exception($this->_ldap);
        }
    }

    /**
     * Low level access to reading object data.
     *
     * This function provides fast access to the Server data.
     *
     * Usually you should use
     *
     * $object = $server->fetch('a server uid');
     * $variable = $object['attribute']
     *
     * to access object attributes. This is slower but takes special object
     * handling into account (e.g. custom attribute parsing).
     *
     * @param string $dn    The object to retrieve.
     * @param string $attrs Restrict to these attributes.
     *
     * @return array|boolean An array of attributes or false if the specified
     *                       object was not found.
     *
     * @throws Horde_Kolab_Server_Exception If the search operation retrieved a
     *                                      problematic result.
     */
    public function read($dn, $attrs = null)
    {
        $params = array('scope' => 'base');
        if (!empty($attrs)) {
            $params['attributes'] = $attrs;
        }

        $result = $this->search(null, $params, $dn);
        $data   = $result->as_struct();
        if (is_a($data, 'PEAR_Error')) {
            throw new Horde_Kolab_Server_Exception($data->getMessage());
        }
        if (empty($data)) {
            throw new Horde_Kolab_Server_Exception(_("Empty result!"));
        }            

        if (!isset($data[$dn])) {
            throw new Horde_Kolab_Server_Exception(sprintf(_("No result found for %s"),
                                                           $dn));
        }
        if (is_a($data[$dn], 'PEAR_Error')) {
            throw new Horde_Kolab_Server_Exception($data[$dn]);
        }
        return $data[$dn];
    }

    /**
     * Save an object.
     *
     * @param string  $uid    The UID of the object to be added.
     * @param array   $data   The attributes of the object to be added.
     * @param boolean $exists Does the object already exist on the server?
     *
     * @return boolean  True if saving succeeded.
     */
    public function save($uid, $data, $exists = false)
    {
        if ($exists === false) {
            $entry  = Net_LDAP2_Entry::createFresh($uid, $data);
            $result = $this->_ldap->add($entry);
            if ($result instanceOf PEAR_Error) {
                throw new Horde_Kolab_Server_Exception($result->getMessage());
            }
        } else {
            $deletes = array();
            foreach ($data as $key => $values) {
                $empty = true;
                foreach ($values as $value) {
                    if (!empty($value)) {
                        $empty = false;
                        break;
                    }
                }
                if ($empty === true) {
                    $deletes[] = $key;
                    unset($data[$key]);
                }
            }
            $result = $this->_ldap->modify($uid, array('delete' => $deletes,
                                                      'replace' => $data));
            if ($result instanceOf PEAR_Error) {
                throw new Horde_Kolab_Server_Exception($result->getMessage());
            }
        }
        return true;
    }

    /**
     * List all objects of a specific type.
     *
     * @param string $type   The type of the objects to be listed
     * @param array  $params Additional parameters.
     *
     * @return array An array of Kolab objects.
     *
     * @throws Horde_Kolab_Server_Exception
     *
     * @todo Sorting
     */
    public function listObjects($type, $params = null)
    {
        if (empty($params['base_dn'])) {
            $base = $this->_base_dn;
        } else {
            $base = $params['base_dn'];
        }

        $result = Horde_Kolab_Server_Object::loadClass($type);
        $vars   = get_class_vars($type);
        $filter = call_user_func(array($type, 'getFilter'));
        $sort   = $vars['sort_by'];

        if (isset($params['sort'])) {
            $sort = $params['sort'];
        }

        $options = array('scope' => 'sub');
        if (isset($params['attributes'])) {
            $options['attributes'] = $params['attributes'];
        } else {
            $options['attributes'] = $vars['_supported_attributes'];
        }

        $result = $this->search($filter, $options, $base);
        $data   = $result->as_struct();
        if (is_a($data, 'PEAR_Error')) {
            throw new Horde_Kolab_Server_Exception($data->getMessage());
        }
        if (empty($data)) {
            return array();
        }

        if ($sort) {
            /* FIXME */
            /* $data = $result->as_sorted_struct(); */
            /*$this->sort($result, $sort); */
        }

        if (isset($params['from'])) {
            $from = $params['from'];
        } else {
            $from = -1;
        }

        if (isset($params['to'])) {
            $sort = $params['to'];
        } else {
            $to = -1;
        }

        if (!empty($vars['required_group'])) {
            $required_group = $this->fetch($vars['required_group'],
                                           'Horde_Kolab_Server_Object_Kolabgroupofnames');
        }

        $objects = array();
        foreach ($data as $uid => $entry) {
            if (!empty($vars['required_group']) && $required_group->isMember($uid)) {
                continue;
            }
            $result = &Horde_Kolab_Server_Object::factory($type, $uid,
                                                          $this, $entry);
            $objects[$uid] = $result;
        }
        return $objects;
    }

    /**
     * Return the root of the UID values on this server.
     *
     * @return string The base UID on this server (base DN on ldap).
     */
    public function getBaseUid()
    {
        return $this->_base_dn;
    }

    /**
     * Return the ldap schema.
     *
     * @return Net_LDAP2_Schema The LDAP schema.
     *
     * @throws Horde_Kolab_Server_Exception If retrieval of the schema failed.
     */
    private function _getSchema()
    {
        if (!isset($this->_schema)) {
            $result = $this->_ldap->schema();
            if ($result instanceOf PEAR_Error) {
                throw new Horde_Kolab_Server_Exception($result->getMessage());
            }
            $this->_schema = &$result;
        }
        return $this->_schema;
    }

    /**
     * Return the schema for the given objectClass.
     *
     * @param string $objectclass Fetch the schema for this objectClass.
     *
     * @return array The schema for the given objectClass.
     *
     * @throws Horde_Kolab_Server_Exception If retrieval of the schema failed.
     */
    protected function getObjectclassSchema($objectclass)
    {
        if (!empty($this->_config['schema_support'])) {
            $schema = $this->_getSchema();
            $info = $schema->get('objectclass', $objectclass);
            if ($info instanceOf PEAR_Error) {
                throw new Horde_Kolab_Server_Exception($info->getMessage());
            }
            return $info;
        }
        return parent::getObjectclassSchema($objectclass);
    }

    /**
     * Return the schema for the given attribute.
     *
     * @param string $attribute Fetch the schema for this attribute.
     *
     * @return array The schema for the given attribute.
     *
     * @throws Horde_Kolab_Server_Exception If retrieval of the schema failed.
     */
    protected function getAttributeSchema($attribute)
    {
        if (!empty($this->_config['schema_support'])) {
            $schema = $this->_getSchema();
            $info = $schema->get('attribute', $attribute);
            if ($info instanceOf PEAR_Error) {
                throw new Horde_Kolab_Server_Exception($info->getMessage());
            }
            return $info;
        }
        return parent::getAttributeSchema($attribute);
    }

    /**
     * Search for object data.
     *
     * @param string $filter The LDAP search filter.
     * @param string $params Additional search parameters.
     * @param string $base   The search base
     *
     * @return array The result array.
     *
     * @throws Horde_Kolab_Server_Exception If the search operation encountered
     *                                      a problem.
     */
    public function search($filter = null, $params = array(), $base = null)
    {
        if (!isset($base)) {
            $base = $this->_base_dn;
        }
        $result = $this->_ldap->search($base, $filter, $params);
        if (is_a($result, 'PEAR_Error')) {
            throw new Horde_Kolab_Server_Exception($result->getMessage());
        }
        return $result;
    }

    /**
     * Get the LDAP object classes for the given DN.
     *
     * @param string $dn DN of the object.
     *
     * @return array An array of object classes.
     *
     * @throws Horde_Kolab_Server_Exception If the object has no
     *                                      object classes.
     */
    public function getObjectClasses($dn)
    {
        $object = $this->read($dn, array(Horde_Kolab_Server_Object::ATTRIBUTE_OC));
        if (!isset($object[Horde_Kolab_Server_Object::ATTRIBUTE_OC])) {
            throw new Horde_Kolab_Server_Exception(sprintf(_("The object %s has no %s attribute!"),
                                                           $dn, Horde_Kolab_Server_Object::ATTRIBUTE_OC));
        }
        $result = array_map('strtolower',
                            $object[Horde_Kolab_Server_Object::ATTRIBUTE_OC]);
        return $result;
    }

    /**
     * Build a search query.
     *
     * Taken from the Turba LDAP driver.
     *
     * @param array $criteria The array of criteria.
     *
     * @return string  An LDAP query filter.
     */
    public function searchQuery($criteria)
    {
        /* Build the LDAP filter. */
        $filter = '';
        if (count($criteria)) {
            foreach ($criteria as $key => $vals) {
                if ($key == 'OR') {
                    $filter .= '(|' . $this->buildSearchQuery($vals) . ')';
                } elseif ($key == 'AND') {
                    $filter .= '(&' . $this->buildSearchQuery($vals) . ')';
                }
            }
        } else {
            /* Accept everything. */
            $filter = '(' . strtolower(Horde_Kolab_Server_Object::ATTRIBUTE_OC) . '=*)';
        }

        /* Add source-wide filters, which are _always_ AND-ed. */
        if (!empty($this->params['filter'])) {
            $filter = '(&' . '(' . $this->params['filter'] . ')' . $filter . ')';
        }
        return $filter;
    }

    /**
     * Build a piece of a search query.
     *
     * Taken from the Turba LDAP driver.
     *
     * @param array $criteria The array of criteria.
     *
     * @return string  An LDAP query fragment.
     */
    protected function buildSearchQuery($criteria)
    {
        $clause = '';
        foreach ($criteria as $key => $vals) {
            if (!empty($vals['OR'])) {
                $clause .= '(|' . $this->buildSearchQuery($vals) . ')';
            } elseif (!empty($vals['AND'])) {
                $clause .= '(&' . $this->buildSearchQuery($vals) . ')';
            } else {
                if (isset($vals['field'])) {
                    require_once 'Horde/String.php';
                    require_once 'Horde/NLS.php';
                    $rhs     = String::convertCharset($vals['test'],
                                                      NLS::getCharset(),
                                                      $this->params['charset']);
                    $clause .= Horde_LDAP::buildClause($vals['field'],
                                                       $vals['op'], $rhs,
                                                       array('begin' => !empty($vals['begin'])));
                } else {
                    foreach ($vals as $test) {
                        if (!empty($test['OR'])) {
                            $clause .= '(|' . $this->buildSearchQuery($test) . ')';
                        } elseif (!empty($test['AND'])) {
                            $clause .= '(&' . $this->buildSearchQuery($test) . ')';
                        } else {
                            $rhs     = String::convertCharset($test['test'],
                                                              NLS::getCharset(),
                                                              $this->params['charset']);
                            $clause .= Horde_LDAP::buildClause($test['field'],
                                                               $test['op'], $rhs,
                                                               array('begin' => !empty($vals['begin'])));
                        }
                    }
                }
            }
        }

        return $clause;
    }

    /**
     * Determine the type of a Kolab object.
     *
     * @param string $uid The UID of the object to examine.
     *
     * @return int The corresponding Kolab object type.
     *
     * @throws Horde_Kolab_Server_Exception If the object type is unknown.
     */
    public function determineType($uid)
    {
        $ocs = $this->getObjectClasses($uid);
        $ocs = array_reverse($ocs);
        foreach ($ocs as $oc) {
            try {
                $class_name = 'Horde_Kolab_Server_Object_' . ucfirst(strtolower($oc));
                Horde_Kolab_Server_Object::loadClass($class_name);
                return $class_name;
            } catch (Horde_Kolab_Server_Exception $e)  {
            }
        }
        if ($oc == 'top') {
            return 'Horde_Kolab_Server_Object';
        }
        throw new Horde_Kolab_Server_Exception(sprintf(_("Unkown object type for UID %s."),
                                                       $uid));
    }

    /**
     * Generates a UID for the given information.
     *
     * @param string $type The type of the object to create.
     * @param string $id   The id of the object.
     * @param array  $info Any additional information about the object to create.
     *
     * @return string The DN.
     *
     * @throws Horde_Kolab_Server_Exception If the given type is unknown.
     */
    public function generateServerUid($type, $id, $info)
    {
        switch ($type) {
        default:
            return sprintf('%s,%s', $id, $this->getBaseUid());
        }
    }
}
