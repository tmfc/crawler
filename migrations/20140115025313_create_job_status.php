<?php

use Phinx\Migration\AbstractMigration;

class CreateJobStatus extends AbstractMigration
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
    	$job_status = $this->table('job_status');
    	$job_status
    		->addColumn('name', 'string', array('limit' => 30))
    		->addColumn('host', 'string', array('limit' => 20))
    		->addColumn('status', 'integer')
    		->addColumn('extra_status', 'string', array('limit' => 8000))
    		->addColumn('start_time', 'datetime')
    		->addColumn('update_time', 'datetime')
    		->addColumn('end_time', 'datetime')
    		->addIndex(array('name', 'host'))
    		->save();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
    	$this->dropTable('job_status');
    }
}