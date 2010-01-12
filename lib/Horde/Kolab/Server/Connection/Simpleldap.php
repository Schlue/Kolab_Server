<?php
/**
 * A simple LDAP setup without read-only slaves.
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
 * A simple LDAP setup without read-only slaves.
 *
 * Copyright 2008-2010 The Horde Project (http://www.horde.org/)
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
class Horde_Kolab_Server_Connection_Simpleldap
implements Horde_Kolab_Server_Connection_Interface
{
    /**
     * The LDAP connection handle.
     *
     * @var Net_LDAP2
     */
    private $_ldap;

    /**
     * Constructor
     *
     * @param Net_LDAP2 $ldap The ldap connection.
     */
    public function __construct(Net_LDAP2 $ldap)
    {
        $this->_ldap = $ldap;
    }

    /**
     * Get the server read connection.
     *
     * @return mixed The connection for reading data.
     */
    public function getRead()
    {
        return $this->_ldap;
    }

    /**
     * Get the server write connection.
     *
     * @return mixed The connection for writing data.
     */
    public function getWrite()
    {
        return $this->_ldap;
    }
}