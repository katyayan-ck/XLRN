<?php 
namespace App\Models;
use Illuminate\Database\Eloquent\SoftDeletes;


class PinCodes extends BaseModel
{
    /**
	 * The database table used by the model.
	 *
	 * @var string
	 */

	protected $table = 'bmpl_pincodes';

	/**
	 * The attributes to be fillable from the model.
	 *
	 * A dirty hack to allow fields to be fillable by calling empty fillable array
	 *
	 * @var array
	 */

	protected $fillable = [];
	protected $guarded = ['id'];
	/**
	 * The attributes excluded from the model's JSON form.
	 *
	 * @var array
	 */
	
}
