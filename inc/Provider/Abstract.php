<?php
/**
 * PostFinance Checkout Prestashop
 *
 * This Prestashop module enables to process payments with PostFinance Checkout (https://www.postfinance.ch).
 *
 * @author customweb GmbH (http://www.customweb.com/)
 * @copyright 2017 - 2019 customweb GmbH
 * @license http://www.apache.org/licenses/LICENSE-2.0 Apache Software License (ASL 2.0)
 */

/**
 * Abstract implementation of a provider.
 */
abstract class PostFinanceCheckout_Provider_Abstract
{
    private static $instances = array();
    private $cacheKey;
    private $data;

    /**
     * Constructor.
     *
     * @param string $cache_key
     */
    protected function __construct($cacheKey)
    {
        $this->cacheKey = $cacheKey;
    }

    /**
     * @return static
     */
    public static function instance()
    {
        $class = get_called_class();
        if (!isset(self::$instances[$class])) {
            self::$instances[$class] = new $class();
        }
        return self::$instances[$class];
    }

    /**
     * Fetch the data from the remote server.
     *
     * @return array
     */
    abstract protected function fetchData();

    /**
     * Returns the id of the given entry.
     *
     * @param mixed $entry
     * @return string
     */
    abstract protected function getId($entry);

    /**
     * Returns a single entry by id.
     *
     * @param string $id
     * @return mixed
     */
    public function find($id)
    {
        if ($this->data == null) {
            $this->loadData();
        }
        
        if (isset($this->data[$id])) {
            return $this->data[$id];
        } else {
            return false;
        }
    }

    /**
     * Returns all entries.
     *
     * @return array
     */
    public function getAll()
    {
        if ($this->data == null) {
            $this->loadData();
        }
        if (!is_array($this->data)) {
            return array();
        }
        return $this->data;
    }

    private function loadData()
    {
        $cachedData = Cache::retrieve($this->cacheKey);
        if ($cachedData !== null) {
            $deserialized = unserialize($cachedData);
            if (is_array($deserialized)) {
                $this->data = $deserialized;
                return;
            }
        }
        
        $this->data = array();
        try {
            foreach ($this->fetchData() as $entry) {
                $this->data[$this->getId($entry)] = $entry;
            }
            Cache::store($this->cacheKey, serialize($this->data));
        } catch (\PostFinanceCheckout\Sdk\ApiException $e) {
        } catch (\PostFinanceCheckout\Sdk\Http\ConnectionException $e) {
        }
    }
}
