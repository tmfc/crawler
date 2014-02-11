<?php

use Phinx\Migration\AbstractMigration;

class ModifyInsuranceInfo extends AbstractMigration
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
    	$this->execute('ALTER TABLE  `insurance_info_js` CHANGE  `last_update_time`  `last_update_time` TIMESTAMP ON UPDATE CURRENT_TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
		$this->execute('ALTER TABLE  `insurance_info_js` CHANGE  `last_update_time`  `last_update_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
    }
}