<?php
namespace Optimizme\Mazen\Helper;

use Braintree\Exception;
use Firebase\JWT\JWT;

/**
 * Class OptimizmeMazenJwt
 * @package Optimizme\Mazen\Helper
 */
class OptimizmeMazenJwt extends \Magento\Framework\App\Helper\AbstractHelper
{
    public $scopeConfig;
    private $resourceConfig;
    private $optimizmeJsonMessages;
    private $cacheTypeList;
    private $cacheFrontendPool;

    /**
     * OptimizmeMazenJwt constructor.
     * @param \Magento\Config\Model\ResourceModel\Config $resourceConfig
     * @param \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList
     * @param \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param OptimizmeMazenJsonMessages $optimizmeJsonMessages
     */
    public function __construct(
        \Magento\Config\Model\ResourceModel\Config $resourceConfig,
        \Magento\Framework\App\Cache\TypeListInterface $cacheTypeList,
        \Magento\Framework\App\Cache\Frontend\Pool $cacheFrontendPool,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Optimizme\Mazen\Helper\OptimizmeMazenJsonMessages $optimizmeJsonMessages
    ) {
    
        $this->resourceConfig = $resourceConfig;
        $this->cacheTypeList = $cacheTypeList;
        $this->cacheFrontendPool = $cacheFrontendPool;
        $this->scopeConfig = $scopeConfig;
        $this->optimizmeJsonMessages = $optimizmeJsonMessages;
    }

    /**
     * Is param a JWT?
     *
     * @param string $s to analyze
     *
     * @return bool
     */
    public function isJwt($s)
    {
        if (is_array($s)) {
            return false;
        }

        if (is_object($s)) {
            return false;
        }

        if (substr_count($s, '.') != 2) {
            return false;
        }

        if (strstr($s, '{')) {
            return false;
        }

        if (strstr($s, '}')) {
            return false;
        }

        if (strstr($s, ':')) {
            return false;
        }

        // all tests OK, seems JWT
        return true;
    }//end isJwt()

    /**
     * @param int $length
     * @return array
     */
    public function generateKeyForJwt($length = 64)
    {
        // generate jwt secret
        $key = substr(
            str_shuffle(
                str_repeat(
                    $x = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
                    ceil(
                        $length / strlen($x)
                    )
                )
            ),
            1,
            $length
        );

        // generate id project
        $idProject = mt_rand(1, 999999999);

        // save and flush cache config
        $this->resourceConfig->saveConfig(
            'optimizme/mazen/jwt'. $idProject,
            $key,
            'default',
            0
        );
        $this->cacheConfigClean();

        $tab = [
            'token' => $key,
            'id_project' => $idProject
        ];
        return $tab;
    }//end generateKeyForJwt()

    /**
     * @param $jwt
     *
     * @return int
     */
    public function getIdProject($jwt)
    {
        $tks = explode('.', $jwt);
        if (count($tks) != 3) {
            $this->optimizmeJsonMessages->setMsgReturn('JWT Error: should be in 3 parts', 'danger');
            return 0;
        }
        $headb64 = $tks[0];
        if (null === ($header = JWT::jsonDecode(JWT::urlsafeB64Decode($headb64)))) {
            $this->optimizmeJsonMessages->setMsgReturn('JWT Error: Header is null', 'danger');
            return 0;
        }
        if (!is_object($header) || !isset($header->idp) || !is_numeric($header->idp)) {
            $this->optimizmeJsonMessages->setMsgReturn('JWT Error: Header not formatted correctly', 'danger');
            return 0;
        } else {
            return $header->idp;
        }
    }

    /**
     * Get saved JWT key
     * @param $idProject
     * @return mixed
     */
    public function getJwtKey($idProject)
    {
        $key = '';

        try {
            $key = $this->scopeConfig->getValue('optimizme/mazen/jwt'.$idProject, 'default', 0);
            if ($key === null) {
                $key = '';
                $this->optimizmeJsonMessages->setMsgReturn('JWT Error: Secret is null', 'danger');
            }
        } catch (Exception $e) {
            $this->optimizmeJsonMessages->setMsgReturn('JWT Error: Exception in get JWT secret', 'danger');
        }

        return $key;
    }//end getJwtKey()

    /**
     * Clean config cache
     */
    public function cacheConfigClean()
    {
        try {
            $types = ['config'];
            foreach ($types as $type) {
                $this->cacheTypeList->cleanType($type);
            }

            foreach ($this->cacheFrontendPool as $cacheFrontend) {
                $cacheFrontend->getBackend()->clean();
            }
        } catch (\Exception $e) {
            // error cleaning cache
            unset($e);
        }
    }//end cacheConfigClean()
}//end class
