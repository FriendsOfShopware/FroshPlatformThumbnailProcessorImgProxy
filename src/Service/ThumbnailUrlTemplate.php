<?php declare(strict_types=1);

namespace Frosh\ThumbnailProcessorImgProxy\Service;

use Frosh\ThumbnailProcessor\Service\ThumbnailUrlTemplateInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class ThumbnailUrlTemplate implements ThumbnailUrlTemplateInterface
{
    /** @var string */
    private $domain;

    /** @var string */
    private $key;

    /** @var string */
    private $salt;

    /** @var string */
    private $resizingType;

    /** @var string */
    private $gravity;

    /** @var int */
    private $enlarge;

    /** @var int */
    private $signatureSize;

    /**
     * @var ThumbnailUrlTemplateInterface
     */
    private $parent;

    public function __construct(SystemConfigService $systemConfigService, ThumbnailUrlTemplateInterface $parent)
    {
        $this->domain = $systemConfigService->get('FroshPlatformThumbnailProcessorImgProxy.config.Domain');
        $this->key = $systemConfigService->get('FroshPlatformThumbnailProcessorImgProxy.config.imgproxykey');
        $this->salt = $systemConfigService->get('FroshPlatformThumbnailProcessorImgProxy.config.imgproxysalt');
        $this->resizingType = $systemConfigService->get('FroshPlatformThumbnailProcessorImgProxy.config.resizingType') ?: 'fit';
        $this->gravity = $systemConfigService->get('FroshPlatformThumbnailProcessorImgProxy.config.gravity') ?: 'sm';
        $this->enlarge = $systemConfigService->get('FroshPlatformThumbnailProcessorImgProxy.config.enlarge') ?: 0;
        $this->signatureSize = $systemConfigService->get('FroshPlatformThumbnailProcessorImgProxy.config.signatureSize') ?: 32;
        $this->parent = $parent;
    }

    /**
     * @param string $mediaUrl
     * @param string $mediaPath
     * @param string $width
     * @param string $height
     */
    public function getUrl($mediaUrl, $mediaPath, $width, $height): string
    {
        $keyBin = pack('H*', $this->key);
        $saltBin = pack('H*', $this->salt);

        if (empty($keyBin) || empty($saltBin)) {
            return $this->parent->getUrl($mediaUrl, $mediaPath, $width, $height);
        }

        $extension = pathinfo($mediaPath, PATHINFO_EXTENSION);
        $encodedUrl = rtrim(strtr(base64_encode($mediaUrl . '/' . $mediaPath), '+/', '-_'), '=');

        $path = "/{$this->resizingType}/{$width}/{$height}/{$this->gravity}/{$this->enlarge}/{$encodedUrl}.{$extension}";
        $signature = hash_hmac('sha256', $saltBin . $path, $keyBin, true);

        if ($this->signatureSize !== 32) {
            $signature = pack('A' . $this->signatureSize, $signature);
        }

        $signature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

        return $this->domain . '/' . $signature . $path;
    }
}
