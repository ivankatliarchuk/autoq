<?php

namespace Autoq\Tests\Api;

use Autoq\Tests\Autoq_TestCase;
use GuzzleHttp\Client;
use Phalcon\Config;
use Phalcon\Db\Adapter\Pdo;
use Phalcon\Di;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Parser;


class JobsApiTest extends Autoq_TestCase
{

    /**
     * @beforeClass
     * Truncate underlying job table
     */
    public static function clearJobDefTable()
    {
        $dBConnectionService = self::$di->get('dBConnectionService');

        /**
         * @var $connection Pdo\Mysql
         */
        $connection = $dBConnectionService->getConnection(self::$config['database']);
        $connection->execute("truncate table job_defs");
        $connection = null;
    }

    /**
     * Setup for each test
     */
    protected function setUp()
    {
        $this->client = new Client([
            'base_uri' => 'http://api'
        ]);
    }

    /**
     * Teardown on each test
     */
    protected function tearDown()
    {
        $this->client = null;
    }


    public function testEmpty()
    {

        $rawResponse = $this->client->get('/jobs/');
        $response = json_decode($rawResponse->getBody());

        $this->assertEquals('success', $response->status);
        $this->assertTrue(count($response->data) == 0);

    }

    /**
     * Test Post of example job 1
     * @throws \Exception
     */
    public function testPostJobExample1()
    {
        $resource = $this->getDataFileResource('example_job_1.yaml');
        $rawResponse = $this->client->request('POST', '/jobs/', ['body' => $resource]);
        $response = json_decode($rawResponse->getBody());

        $this->assertObjectHasAttribute('status', $response);
        $this->assertEquals('success', $response->status);

        $this->assertObjectHasAttribute('data', $response);

        $data = $response->data;

        $this->assertTrue(is_numeric($data->id) && (int)$data->id > 0);

        $sourceYaml = $this->loadDataFileAsYaml('example_job_1.yaml');

        $this->assertEquals($sourceYaml['name'], $data->name);
        $this->assertEquals($sourceYaml['query'], $data->query);

        $this->assertNotNull($data->created);
        $this->assertNull($data->updated);

        $this->assertEquals(count($sourceYaml['outputs']), count($data->outputs));

        //Outputs are currently returned unchanged - checks this is the case
        $index = 0;
        foreach ($sourceYaml['outputs'] as $output) {
            $this->assertArraySubset((array)$data->outputs[$index++], $output);
        }

        return $data->id;
    }


    /**
     * @depends testPostJobExample1
     * @param $id
     */
    public function testGetJob($id)
    {

        $rawResponse = $this->client->get("/jobs/$id");
        $response = json_decode($rawResponse->getBody());

        $this->assertEquals('success', $response->status);

        $data = $response->data;

        $this->assertEquals($id, $data->id);
        $this->assertNotNull($data->created);

        return $id;

    }


    /**
     * @depends testGetJob
     * @param $id
     */
    public function testupdateJob($id)
    {

        $resource = $this->getDataFileResource('example_job_2.yaml');
        $rawResponse = $this->client->request('PUT', "/jobs/$id", ['body' => $resource]);

        $response = json_decode($rawResponse->getBody());

        $this->assertEquals('success', $response->status);

        $data = $response->data;

        $this->assertEquals($id, $data->id);
        $this->assertNotNull($data->created);
        $this->assertNotNull($data->updated);

        return $id;

    }

    /**
     * @depends testupdateJob
     * @param $id
     * @return bool
     */
    public function testDeleteJob($id)
    {

        $rawResponse = $this->client->delete("/jobs/$id");
        $response = json_decode($rawResponse->getBody());

        $this->assertEquals('success', $response->status);

        $this->assertObjectNotHasAttribute('data', $response);

    }

    /**
     * Test get on non existent job
     */
    public function testNoJobforGet()
    {

        $rawResponse = $this->client->get("/jobs/999");
        $response = json_decode($rawResponse->getBody());

        $this->assertEquals('error', $response->status);
        $this->assertEquals("Job with ID: 999 does not exist", $response->reason);

    }

    /**
     * Test update on non existent job
     */
    public function testNoJobforUpdate()
    {

        $resource = $this->getDataFileResource('example_job_2.yaml');
        $rawResponse = $this->client->request('PUT', "/jobs/999", ['body' => $resource]);

        $response = json_decode($rawResponse->getBody());

        $this->assertEquals('error', $response->status);
        $this->assertEquals("Job with ID: 999 does not exist", $response->reason);

    }

    /**
     * Test delete on non existent job
     */
    public function testNoJobforDelete()
    {

        $rawResponse = $this->client->delete("/jobs/999");
        $response = json_decode($rawResponse->getBody());

        $this->assertEquals('error', $response->status);
        $this->assertEquals("Job with ID: 999 does not exist", $response->reason);

    }

    /**
     * Read into memory a YAML file
     * @param $filename
     * @return bool|mixed
     */
    private function loadDataFileAsYaml($filename)
    {

        $filepath = __DIR__ . "/data/$filename";

        $data = false;

        try {

            $yaml = new Parser();
            $data = $yaml->parse(file_get_contents($filepath));

        } catch (ParseException $e) {
            echo $e->getMessage() . PHP_EOL;
        }

        return $data;
    }

    /**
     * Get file resource for a test data file
     * @param $filename
     * @return resource
     * @throws \Exception
     */
    private function getDataFileResource($filename)
    {

        $filepath = __DIR__ . "/data/$filename";

        if (($fh = fopen($filepath, 'r')) === false) {
            throw new \Exception("Could not open: $filepath");
        }

        return $fh;
    }

}