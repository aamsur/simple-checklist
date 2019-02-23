<?php

class ChecklistTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testGetChecklist()
    {
        $response = $this->call('GET', '/api/v1/login?email=surganda@gmail.com&password=123456');
        $content  = json_decode($response->getContent());
        
        $headers = array(
            'Content-Type'  => 'application/json',
            'Authorization' => $content->api_key,
        );
        $server  = $this->transformHeadersToServerVars($headers);
        
        $response = $this->call('GET', '/api/v1/checklists', [], [], [], $server);
        $this->assertEquals(200, $response->status());
        
        $response = $this->call('PATCH', '/api/v1/checklists', [], [], [], $server);
        $this->assertEquals(405, $response->status());
    }
    
    /**
     * failed login will return 401
     *
     * @return void
     */
    public function testFailLogin()
    {
        $response = $this->call('GET', '/api/v1/login?email=surganda@gmail.com&password=12345');
        
        $this->assertEquals(401, $response->status());
    }
    
    /**
     * wrong path test
     *
     * @return void
     */
    public function testNotFoundLogin()
    {
        $response = $this->call('GET', '/api/login?email=surganda@gmail.com&password=123456');
        
        $this->assertEquals(404, $response->status());
    }
}
