<?php

namespace APubSub\Backend\Drupal7;

use APubSub\Misc;
/**
 * Handles message type normalisation using an external database table in
 * order to leave the apb_msg table lightweight
 */
class TypeRegistry
{
    /**
     * Known types cache
     *
     * @var array
     */
    private $types;

    /**
     * @var D7Backend
     */
    private $backend;

    public function __construct(D7Backend $backend)
    {
        $this->backend = $backend;
    }

    /**
     * Load or refresh types cache
     */
    protected function loadCache()
    {
        $this->types = $this
            ->backend
            ->getConnection()
            ->query("SELECT id, type FROM {apb_msg_type}")
            ->fetchAllKeyed()
        ;
    }

    /**
     * Get type identifier
     *
     * @param string $type
     *   Message type
     * @param boolean $createIfMissing
     *   Create it if missing
     *
     * @return int         Message type id
     */
    public function getTypeId($type, $createIfMissing = true)
    {
        if (null === $this->types) {
            $this->loadCache();
        }

        if (false === ($key = array_search($type, $this->types, true))) {

            if (!$createIfMissing) {
                return null;
            }

            try {
                $this
                    ->backend
                    ->getConnection()
                    ->insert('apb_msg_type')
                    ->fields(array('type' => $type))
                    ->execute()
                ;

            } catch (\PDOException $e) {
                // Another thread went doing this at the same time and created
                // the same type, ignore error and continue
            }

            $this->loadCache();

            return array_search($type, $this->types, true);

        } else {
            return $key;
        }
    }

    /**
     * Get type from identifier
     *
     * @param int $id Message type id
     *
     * @return string Message type
     */
    public function getType($id)
    {
        if (null === $id) {
            // The caller might call us using a row from database that contains
            // a strict null, it is useless for us to do any query since null
            // typed messages are valid by the MessageInterface signature
            return null;
        }

        if (null === $this->types) {
            $this->loadCache();
        }

        if (!isset($this->types[$id])) {
            // Someone may have created it before we loaded the cache
            $this->loadCache();

            if (!isset($this->types[$id])) {
                // It seems that the type really does not exists, mark it as
                // being wrong in order to avoid to refresh the cache too often
                // and return a null type
                $this->types[$id] = false;
            }
        }

        if (false === $this->types[$id]) {
            return null;
        } else {
            return $this->types[$id];
        }
    }

    /**
     * Convert given query condition value which is supposed to contain types
     * identifiers to integer identifiers
     *
     * This function will take care of awaited query format
     */
    public function convertQueryCondition($value)
    {
        // First fetch the type
        if (null === $value) {
            return 0;
        }

        if (!is_array($value)) {
            $value = [$value];
        }

        $hasOperator = false;

        // FIXME Sorry for this
        if (!Misc::isIndexed($value)) {
            // We have an operator.
            $operator = array_keys($value)[0];
            $values = $value[$operator][0];
            $hasOperator = true;
        } else {
            $values = $value;
        }

        foreach ($values as $key => $type) {
            if (null === $type) {
                $values[$key] = 0;
            } else if ($typeId = $this->getTypeId($type, false)) {
                $values[$key] = $typeId;
            } else {
                unset($values[$key]);
            }
        }

        if (empty($values)) {
            // It should not have been empty, this is an impossible
            // condition
            $values = [-1];
        }

        if ($hasOperator) {
            return [$operator => $values];
        } else {
            return $values;
        }
    }
}
