<?php

namespace App\Util;

use Aws\Credentials\Credentials;
use Aws\S3\S3Client;

class AwsS3Util
{
    const SDK_VERSION = 'latest';

    private static $instance = null;

    public static function getInstance()
    {
        if (empty(self::$instance)) {
            self::$instance = new AwsS3Util();
        }

        return self::$instance;
    }

    public function getConfig()
    {
        $accessKey = getenv('S3_ACCESS_KEY');
        $secretKey = getenv('S3_SECRET_KEY');
        $region = getenv('S3_REGION');
        $bucket = getenv('S3_BUCKET');
        $directory = getenv('S3_DIRECTORY');
        return [
            'accessKey' => $accessKey,
            'secretKey' => $secretKey,
            'directory' => $directory
        ];
    }

    public function deleteObject($path)
    {
        $accessKey = getenv('S3_ACCESS_KEY');
        $secretKey = getenv('S3_SECRET_KEY');
        $region = getenv('S3_REGION');
        $bucket = getenv('S3_BUCKET');
        $directory = getenv('S3_DIRECTORY');
        $version = self::SDK_VERSION;
        $path = $directory.'/'.$path;

        $credentials = new Credentials($accessKey, $secretKey);

        $s3Client = new S3Client([
//            'profile' => 'default',
            'region' => $region,
            'version' => $version,
            'credentials' => $credentials,
        ]);

        // Delete an object from the bucket.
        $s3Client->deleteObject([
            'Bucket' => $bucket,
            'Key' => $path,
        ]);

        $apcuGetKey = 'GET_'.$path;
        if (apcu_exists($apcuGetKey)) {
            apcu_delete($apcuGetKey);
        }
    }

    public function getObjectWriteForm($path, $expires = '+2 hours')
    {
//        $apcuGetKey = 'POST_'.$path;
//        if (apcu_exists($apcuGetKey)) {
//            return apcu_fetch($apcuGetKey);
//        }

        $accessKey = getenv('S3_ACCESS_KEY');
        $secretKey = getenv('S3_SECRET_KEY');
        $region = getenv('S3_REGION');
        $bucket = getenv('S3_BUCKET');
        $directory = getenv('S3_DIRECTORY');
        $version = self::SDK_VERSION;

        $credentials = new Credentials($accessKey, $secretKey);

        $path = $directory.'/'.$path;

        //Creating a presigned request
        $s3Client = new S3Client([
//            'profile' => 'default',
            'region' => $region,
            'version' => $version,
            'credentials' => $credentials,
        ]);


//        apcu_store($apcuGetKey, $url);

        /////////////////////////////////////////
        ///
        ///
        ///

// Set some defaults for form input fields
        $formInputs = ['acl' => 'private'];
        $pathPieces = explode('/', $path);
        array_pop($pathPieces);
        $directoryPath = implode('/', $pathPieces);
// Construct an array of conditions for policy
        $options = [
            ['acl' => 'private'],
            ['bucket' => $bucket],
            ['starts-with', '$key', $directoryPath],
        ];

// Optional: configure expiration time string
//        $expires = '+2 hours';

        $postObject = new \Aws\S3\PostObjectV4(
            $s3Client,
            $bucket,
            $formInputs,
            $options,
            $expires
        );

// Get attributes to set on an HTML form, e.g., action, method, enctype
        $formAttributes = $postObject->getFormAttributes();

// Get form input fields. This will include anything set as a form input in
// the constructor, the provided JSON policy, your AWS access key ID, and an
// auth signature.
        $formInputs = $postObject->getFormInputs();
        return [
            'attributes' => $formAttributes,
            'inputs' => $formInputs
        ];
    }

    public function getObjectReadUrl($path, $expr = '+7 days')
    {
        $apcuGetKey = 'GET_'.$path;
        if (apcu_exists($apcuGetKey)) {
            return apcu_fetch($apcuGetKey);
        }

        $accessKey = getenv('S3_ACCESS_KEY');
        $secretKey = getenv('S3_SECRET_KEY');
        $region = getenv('S3_REGION');
        $bucket = getenv('S3_BUCKET');
        $directory = getenv('S3_DIRECTORY');
        $version = self::SDK_VERSION;

        $credentials = new Credentials($accessKey, $secretKey);

        $path = $directory.'/'.$path;

        //Creating a presigned request
        $s3Client = new S3Client([
//            'profile' => 'default',
            'region' => $region,
            'version' => $version,
            'credentials' => $credentials,
        ]);

        $cmd = $s3Client->getCommand('GetObject', [
            'Bucket' => $bucket,
            'Key' => $path,
        ]);

        $request = $s3Client->createPresignedRequest($cmd, $expr);
        $url = (string) $request->getUri();

        apcu_store($apcuGetKey, $url);

        return $url;
    }
}
