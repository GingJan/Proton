<?php
namespace Zjien\Proton\Repository;

use Gregwar\Captcha\CaptchaBuilder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Zjien\Quark\Facades\Cache;

class PCCodeRepository
{
    /**
     * @var bool
     */
    protected $cacheEnabled;

    /**
     * @var string
     */
    protected $cacheFailedIpKey;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var int
     */
    protected $expire;

    protected $prefix;

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->cacheEnabled = Config::get('proton.cache.enabled', false);
        $this->cacheFailedIpKey = Config::get('proton.cache.failedIpKeyName', 'login.failed:ip');
        $this->expire = Config::get('proton.repository.login.expire', 180);
        $this->prefix = Config::get('proton.cache.prefix', 'proton');
    }

    /**
     * generate verify code image.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function generate()
    {
        $captcha = new CaptchaBuilder();

        $encrypt = $this->savePhrase($captcha->getPhrase());

        return $this->response($captcha->build()->get(), ['Phrase' => $encrypt]);
    }

    /**
     * @param $image
     * @param array $headers
     * @return \Illuminate\Http\Response
     */
    protected function response($image, array $headers = [])
    {
        $mime = finfo_buffer(finfo_open(FILEINFO_MIME_TYPE), $image);
        $length = strlen($image);

        $response = \Response::make($image, 200, $headers);
        $response->header('Content-Type', $mime);
        $response->header('Content-Length', $length);
        return $response;
    }

    /**
     * encode and encrypt phrase.
     *
     * @param string $phrase
     * @return string $token
     */
    public function savePhrase($phrase)
    {
        if ($this->cacheEnabled) {
            $phraseKey = Config::get('proton.cache.phrase', 'phrase') . ':';
            $phraseKey = $this->prefix . $phraseKey . $this->request->getClientIp();
            return Cache::string($phraseKey, $this->expire)->set($phrase);
        }



    }

    /**
     * @return SignerInterface
     * @throws \InvalidArgumentException
     */
    protected function getSigner()
    {
        $algo = config('proton.algo');
        $signerClass = sprintf('Namshi\\JOSE\\Signer\\OpenSSL\\%s', $algo);

        if (class_exists($signerClass)) {
            return new $signerClass();
        }

        throw new \InvalidArgumentException(
            sprintf("The algorithm '%s' is not supported for %s", $algo, 'OpenSSL'));
    }


    public function check(Request $request, $input)
    {
        $isCached = false;
        if ($this->cacheEnabled) {
            try {
                $isCached = Cache::sets($this->cacheFailedIpKey)->has($request->getClientIp());
            } catch (\Exception $e) {
                \Log::notice($e->getFile() . '|' . $e->getLine() . $e->getMessage());
            }
        }

        if ($isCached) {
            $phrase = $request->header('Phrase');
            if (is_null($phrase)) {
                throw new BadRequestHttpException('verifyCode invalid', null, 40001);
            }

            //ip 有效期 是否正确
            list($payload, $encrypt) = explode('.', $phrase);
            $segment = json_decode(base64_decode($payload), true);
            if ($request->getClientIp() != $segment['ip'] || $_SERVER['REQUEST_TIME'] >= $segment['expire']) {
//                throw new BadRequestHttpException('verifyCode invalid', null, 40000);
                throw new BadRequestHttpException('verifyCode invalid', null, 40002);
            }

            //判断验证码是否有效
            if (hash_hmac('sha256', $payload . $request->get('verifyCode'), config('jwt.secret')) != $encrypt) {
//                $this->invalidateVerifyCode($encrypt);
                throw new BadRequestHttpException('verifyCode invalid', null, 40003);
            }
        }
    }

    public function abandon()
    {

    }

    public function record(Request $request)
    {
        try {
            Cache::sets($this->cacheFailedIpKey)->add($request->getClientIp());//记录用户登录次数，第一次登录不需要验证码，密码错误后，第二次则需要
        } catch (\Exception $e) {
            \Log::notice($e->getFile() . '|' . $e->getLine() . $e->getMessage());
        }

        $this->response()->errorUnauthorized('invalid_credentials');
    }

}