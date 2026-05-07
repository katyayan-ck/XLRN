<?php
	
	namespace App\Models;
	
	use Illuminate\Database\Eloquent\SoftDeletes;
	use DataTables,Auth;
	//use Rennokki\QueryCache\Traits\QueryCacheable;
	namespace App\Models;
	
	
	class EnumMaster extends BaseModel
	{
		//use QueryCacheable;
		public $cacheFor = 60*60*24; 
		protected $table = 'bmpl_enum_master';
		
		protected $fillable = [];
		protected $guarded = ['id'];
		
		protected function getCacheBaseTags(): array
		{
			return [
            'EnumMaster_tag',
			];
		}
		
		
		public function getDmsStatus()
		{
			return $this->select('id','name','value')->where('tbl_name','PurchaseBilling')->where('col_name','dms_status')->where('status',1)->get();
		}
		
		public function getCrmStatus()
		{
			return $this->select('id','name','value')->where('tbl_name','PurchaseBilling')->where('col_name','crm_status')->where('status',1)->get();
		}
		
		public function master()
		{
			return $this->belongsTo('App\Models\Enumrator','parent_id');
		}
		
		public function parent()
		{
			return $this->belongsTo('App\Models\Statuses', 'parent_id', 'id');
		}
		
	}
	
	
