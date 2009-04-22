<?php
/**
 * The driver for handling the Kolab user database structure.
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
 * This class provides methods to deal with Kolab objects stored in
 * the standard Kolab LDAP db.
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
class Horde_Kolab_Server_Structure_Kolab extends Horde_Kolab_Server_Structure_Ldap
{
    /**
     * Returns the set of objects supported by this structure.
     *
     * @return array An array of supported objects.
     */
    public function getSupportedObjects()
    {
        return array(
            'Horde_Kolab_Server_Object',
            'Horde_Kolab_Server_Object_Groupofnames',
            'Horde_Kolab_Server_Object_Person',
            'Horde_Kolab_Server_Object_Organizationalperson',
            'Horde_Kolab_Server_Object_Inetorgperson',
            'Horde_Kolab_Server_Object_Kolab',
            'Horde_Kolab_Server_Object_Kolabinetorgperson',
            'Horde_Kolab_Server_Object_Kolabgermanbankarrangement',
            'Horde_Kolab_Server_Object_Kolabpop3account',
            'Horde_Kolab_Server_Object_Kolabgroupofnames',
            'Horde_Kolab_Server_Object_Kolabsharedfolder',
            'Horde_Kolab_Server_Object_Kolab_Address',
            'Horde_Kolab_Server_Object_Kolab_Administrator',
            'Horde_Kolab_Server_Object_Kolab_Distlist',
            'Horde_Kolab_Server_Object_Kolab_Domainmaintainer',
            'Horde_Kolab_Server_Object_Kolab_Maintainer',
            'Horde_Kolab_Server_Object_Kolab_User',
        );
    }

    /**
     * Determine the type of an object by its tree position and other
     * parameters.
     *
     * @param string $uid The UID of the object to examine.
     *
     * @return string The class name of the corresponding object type.
     *
     * @throws Horde_Kolab_Server_Exception If the object type is unknown.
     */
    public function determineType($uid)
    {
        $oc = $this->server->getObjectClasses($uid);
        // Not a user type?
        if (!in_array('kolabinetorgperson', $oc)) {
            // Is it a group?
            if (in_array('kolabgroupofnames', $oc)) {
                return 'Horde_Kolab_Server_Object_Kolabgroupofnames';
            }
            // Is it an external pop3 account?
            if (in_array('kolabexternalpop3account', $oc)) {
                return 'Horde_Kolab_Server_Object_Kolabpop3account';
            }
            // Is it a shared Folder?
            if (in_array('kolabsharedfolder', $oc)) {
                return 'Horde_Kolab_Server_Object_Kolabsharedfolder';
            }
            return parent::determineType($uid);
        }

        $groups = $this->server->getGroups($uid);
        if (!empty($groups)) {
            if (in_array('cn=admin,cn=internal,' . $this->server->getBaseUid(), $groups)) {
                return 'Horde_Kolab_Server_Object_Kolab_Administrator';
            }
            if (in_array('cn=maintainer,cn=internal,' . $this->server->getBaseUid(),
                         $groups)) {
                return 'Horde_Kolab_Server_Object_Kolab_Maintainer';
            }
            if (in_array('cn=domain-maintainer,cn=internal,' . $this->server->getBaseUid(),
                         $groups)) {
                return 'Horde_Kolab_Server_Object_Kolab_Domainmaintainer';
            }
        }

        if (strpos($uid, 'cn=external') !== false) {
            return 'Horde_Kolab_Server_Object_Kolab_Address';
        }

        return 'Horde_Kolab_Server_Object_Kolab_User';
    }

    /**
     * Generates a UID for the given information.
     *
     * @param string $type The class name of the object to create.
     * @param string $id   The id of the object.
     * @param array  $info Any additional information about the object to create.
     *
     * @return string The UID.
     *
     * @throws Horde_Kolab_Server_Exception If the given type is unknown.
     */
    public function generateServerUid($type, $id, $info)
    {
        switch ($type) {
        case 'Horde_Kolab_Server_Object_Kolab_User':
            if (empty($info['user_type'])) {
                return parent::generateServerUid($type, $id, $info);
            } else if ($info['user_type'] == Horde_Kolab_Server_Object_Kolab_User::USERTYPE_INTERNAL) {
                return parent::generateServerUid($type,
                                                 sprintf('%s,cn=internal', $id),
                                                 $info);
            } else if ($info['user_type'] == Horde_Kolab_Server_Object_Kolab_User::USERTYPE_GROUP) {
                return parent::generateServerUid($type,
                                                 sprintf('%s,cn=groups', $id),
                                                 $info);
            } else if ($info['user_type'] == Horde_Kolab_Server_Object_Kolab_User::USERTYPE_RESOURCE) {
                return parent::generateServerUid($type,
                                                 sprintf('%s,cn=resources', $id),
                                                 $info);
            } else {
                return parent::generateServerUid($type, $id, $info);
            }
        case 'Horde_Kolab_Server_Object_Kolab_Address':
            return parent::generateServerUid($type,
                                             sprintf('%s,cn=external', $id),
                                             $info);
        case 'Horde_Kolab_Server_Object_Kolabgroupofnames':
        case 'Horde_Kolab_Server_Object_Kolab_Distlist':
            if (!isset($info['visible']) || !empty($info['visible'])) {
                return parent::generateServerUid($type, $id, $info);
            } else {
                return parent::generateServerUid($type,
                                                 sprintf('%s,cn=internal', $id),
                                                 $info);
            }
        case 'Horde_Kolab_Server_Object_Kolabsharedfolder':
        case 'Horde_Kolab_Server_Object_Kolab_Administrator':
        case 'Horde_Kolab_Server_Object_Kolab_Maintainer':
        case 'Horde_Kolab_Server_Object_Kolab_Domainmaintainer':
        default:
            return parent::generateServerUid($type, $id, $info);
        }
    }
}
