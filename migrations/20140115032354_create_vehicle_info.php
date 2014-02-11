<?php

use Phinx\Migration\AbstractMigration;

class CreateVehicleInfo extends AbstractMigration
{
    /**
     * Change Method.
     *
     * More information on this method is available here:
     * http://docs.phinx.org/en/latest/migrations.html#the-change-method
     *
     * Uncomment this method if you would like to use it.
     *
    public function change()
    {
    }
    */
    
    /**
     * Migrate Up.
     */
    public function up()
    {
    	$vehicle_info_js = $this->table('vehicle_info_js');
    	$vehicle_info_js
    	->addColumn('vehicle_number_type', 'integer',array('limit' => 2))
    	->addColumn('vehicle_number', 'string', array('limit' => 9))
    	->addColumn('vehicle_brand', 'string',array('limit' => 30))
    	->addColumn('vehicle_type', 'string', array('limit' => 30))
    	->addColumn('vehicle_id', 'string', array('limit' => 20))
    	->addColumn('engine_id', 'string', array('limit' => 20))
    	->addColumn('usage', 'string', array('limit' => 1))
    	->addColumn('owner', 'string', array('limit' => 100,'null' => true))
    	->addColumn('owner_id', 'string', array('limit' => 25,'null' => true))
    	->addColumn('enroll_time', 'datetime')
    	->addColumn('telphone', 'string', array('limit' => 20,'null' => true))
    	->addColumn('mobile', 'string', array('limit' => 20,'null' => true))
    	->addColumn('insurance_expire_date', 'date',array('null' => true))
    	->addColumn('status', 'integer', array('limit' => 4))
    	->addIndex('vehicle_id',array('unique' => true))
    	->addIndex('status')
    	->save();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
    	$this->dropTable('vehicle_info_js');
    }
}