<?php

use Phinx\Migration\AbstractMigration;

class CreateInsuranceInfo extends AbstractMigration
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
    	$insurance_info_js = $this->table('insurance_info_js');
    	$insurance_info_js
    	->addColumn('vehicle_id', 'string',array('limit' => 20))
    	->addColumn('license_no', 'string', array('limit' => 7))
    	->addColumn('license_type', 'string',array('limit' => 5))
    	->addColumn('policy_no', 'string', array('limit' => 50))
    	->addColumn('company_code', 'string', array('limit' => 30))
    	->addColumn('insurance_type', 'string', array('limit' => 6))
    	->addColumn('operate_date', 'date')
    	->addColumn('start_date', 'date')
    	->addColumn('end_date', 'date')
    	->addColumn('claim_query_no', 'string', array('limit' => 50))
    	->addColumn('claim_status', 'string', array('limit' => 10))
    	->addColumn('damage_date', 'date', array('null' => true))
    	->addColumn('report_date', 'date', array('null' => true))
    	->addColumn('clain_date', 'date', array('null' => true))
    	->addColumn('endcase_date', 'date', array('null' => true))
    	->addColumn('driver_name', 'string', array('limit' => 10,'null' => true))
    	->addColumn('estimate_loss', 'float',array('precision' => 8,'scale' => 2))
    	->addColumn('sum_paid', 'float',array('precision' => 8,'scale' => 2))
    	->addColumn('sum_all_paid', 'float',array('precision' => 8,'scale' => 2))
    	->addColumn('currency', 'string',array('limit' => 5))
    	->addColumn('indemnity_duty', 'string', array('limit' => 10))
    	->addColumn('claim_no', 'string', array('limit' => 7))
    	->addColumn('claim_type', 'string',array('limit' => 5))
    	->addColumn('regist_no', 'string', array('limit' => 50))
    	->addColumn('case_no', 'string', array('limit' => 30))
    	->addColumn('claim_cyc', 'integer')
    	->addColumn('valid_status', 'string',array('limit' => 5))
    	->addColumn('policy_confirm_no', 'string', array('limit' => 50))
    	->addColumn('confirm_sequence_no', 'string', array('limit' => 30))
    	->addColumn('end_case_cyc', 'integer')
    	->addColumn('remark', 'string', array('limit' => 200))
    	->addColumn('last_update_time', 'timestamp', array('default' => 'CURRENT_TIMESTAMP'))
    	->addIndex('vehicle_id')
    	->addIndex('policy_no')
    	->save();
    }

    /**
     * Migrate Down.
     */
    public function down()
    {
		$this->dropTable('insurance_info_js');
    }
}