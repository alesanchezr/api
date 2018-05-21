<?php
namespace Tests;

class CatalogTest extends BaseTestCase
{
    protected $app;
    
    public function setUp(){
        
        parent::setUp();
        $this->app->addRoutes(['catalog']);
        
    }
    
    public function testForCountry() {
        $this->mockAPICall(['REQUEST_METHOD' => 'GET','REQUEST_URI' => '/catalog/countries/'])
            ->withPropertiesAndValues(['chile' => ['santiago']])
            ->expectSuccess();
    } 
    
    public function testForTechnologies() {
        
        $responseObj = $this->mockAPICall(['REQUEST_METHOD' => 'GET','REQUEST_URI' => '/catalog/technologies/'])
                        ->expectSuccess()
                        ->getParsedBody();
        $this->assertTrue(is_array($responseObj->data));
        $this->assertFalse(count($responseObj->data) == 0);
    }
    
    /*public function testGetAtemplatedifficulties() {
        
        $this->mockAPICall(['REQUEST_METHOD' => 'GET','REQUEST_URI' => '/catalog/atemplate_difficulties/'])
                        ->expectSuccess();
    }*/
    
    public function testForCohortStages() {
        
        $responseObj = $this->mockAPICall(['REQUEST_METHOD' => 'GET','REQUEST_URI' => '/catalog/cohort_stages/'])
                        ->expectSuccess()
                        ->getParsedBody();
        $this->assertTrue(is_array($responseObj->data));
        $this->assertTrue(in_array('not-started',$responseObj->data));
    } 
    
}