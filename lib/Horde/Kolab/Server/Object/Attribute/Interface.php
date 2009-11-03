<?php
/**
 * The interface representing Kolab object attributes.
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
 * The interface representing Kolab object attributes.
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
interface Horde_Kolab_Server_Object_Attribute_Interface
{
    /**
     * Return the value of this attribute.
     *
     * @return array The value(s) of this attribute.
     *
     * @throws Horde_Kolab_Server_Exception If retrieval of the value failed.
     */
    public function value();

    /**
     * Return the new internal state for this attribute.
     *
     * @param array $changes The object data that should be updated.
     *
     * @return array The resulting internal state.
     *
     * @throws Horde_Kolab_Server_Exception If storing the value failed.
     */
    public function update(array $changes);

    /**
     * Return the object this attribute belongs to.
     *
     * @return Horde_Kolab_Server_Object The object.
     */
    public function getObject();

    /**
     * Return the internal name of this attribute.
     *
     * @return string The name of this object.
     */
    public function getInternalName();

    /**
     * Return the external name of this attribute.
     *
     * @return string The name of this object.
     */
    public function getExternalName();

    /**
     * Return if this attribute is undefined in the given data array.
     *
     * @param array $changes The data array to test.
     *
     * @return string The name of this object.
     */
    public function isEmpty(array $changes);

    /**
     * Indicate that a value will be saved by deleting it from the original data
     * array.
     *
     * @param array &$changes The object data that should be changed.
     *
     * @return NULL
     */
    public function consume(array &$changes);
}