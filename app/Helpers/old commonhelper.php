<?php

namespace App\Helpers;

use App\Models\Vehicle;
use App\Models\Branches;
use App\Models\EnumMaster;
use App\Models\Enumrator;
use App\Models\PriceList;
use App\Models\Accessories;
use App\Models\Colors;
use App\Models\PlSource;
use App\Models\NoColors;
use App\Models\Settings;
use App\Models\Person;

use App\Models\UserBranch;
use App\Models\UserDesignation;
use App\Models\DesignationDepartment;
use App\Models\DepartmentVertical;
use App\Models\PinCodes;

use App\Models\User;
use App\Helpers\QuotesHelper;
use App\Helpers\ColorHelper;
use App\Helpers\ExtrasHelper;
use App\Helpers\RTOHelper;
use App\Helpers\InsuranceHelper;
use App\Helpers\BranchHelper;
use App\Helpers\PricingHelper;
use App\Helpers\TaskHelper;
use App\Helpers\UserHelper;
use App\Helpers\VehicleHelper;
use App\Models\X_Branch;
use App\Models\X_Location;
use Auth;

use Illuminate\Support\Str;
use Carbon\Carbon;



class CommonHelper
{
    public static function getCurrentTimezone()
    {
        return config('app.timezone');
    }

    public static function formatDate($date)
    {
        return Carbon::parse($date)->timezone(self::getCurrentTimezone())->format('Y-m-d H:i:s');
    }

    public static function setUserLocale($locale)
    {
        session(['locale' => $locale]);
        app()->setLocale($locale);
    }

    public static function getBranches()
    {
        $data = array();
        $tmp = X_Branch::select('id', 'name', 'abbr')->where('status', 1)->get()->toArray();
        foreach ($tmp as $arr) {
            $data[$arr['id']] = array('id' => $arr['id'], 'name' => $arr['name'], 'code' => $arr['abbr']);
        }
        return $data;
    }

    public static function getStockLocations()
    {
        $data = array();
        $tmp = X_Location::select('id', 'name', 'branch_id', 'abbr', 'demibranch')
            ->where('stock_location', 1)
            ->where('status', 1)
            ->orderBy('d_order')
            ->get()
            ->toArray();
        foreach ($tmp as $arr) {
            $data[$arr['id']] = array('id' => $arr['id'], 'name' => $arr['name'], 'code' => $arr['abbr']);
        }
        return $data;
    }


    public static function getLocations()
    {
        $tmp = X_Location::select('id', 'name', 'abbr', 'demibranch')
            ->where('status', 1)
            ->whereNotIn('id', [17, 18])
            ->orderBy('d_order')
            ->get()
            ->toArray();

        $data = [];
        foreach ($tmp as $arr) {
            $data[$arr['id']] = [
                'id' => $arr['id'],
                'name' => $arr['name'] . ' - ' . $arr['abbr'], // Combine name and code (abbr)
                'code' => $arr['abbr'],
                'demibranch' => $arr['demibranch']
            ];
        }
        return $data;
    }

    public static function addGlobalKey($key, $val, $rem = null)
    {
        $res = Settings::updateOrCreate(['data_key' => strtoupper($key)], ['value' => $val, 'details' => $rem]);
        if ($res)
            return true;
        else
            return false;
    }

    // public static function getUserName($id = null)
    // {
    //     if (empty($id))
    //         $id = Auth::user()->id;
    //     $user = User::find($id);
    //     //$pr = Person::find($user->person_id);
    //     $depts = explode(',', $user->department);
    //     if (count($depts) >= 1)
    //         $dept = CommonHelper::enumValueById($depts[0]);
    //     else
    //         $dept = "";
    //     return $user->name . " [" . $user->mobile . "] ";
    //     //dd($user->toArray());
    // }
    public static function getUserName($id = null)
    {
        if (empty($id)) {
            $id = backpack_auth()->id(); // current logged-in user
        }

        $user = \App\Models\User::find($id);

        if (!$user) {
            return 'User not found (ID: ' . $id . ')'; // safe fallback – crash nahi hoga
        }

        // Ab safe hai – department pe ja sakte ho
        $depts = explode(',', $user->department ?? '');

        $dept = '';
        if (!empty($depts[0])) {
            $dept = self::enumValueById($depts[0]) ?? '';
        }

        return $user->name . " [" . ($user->mobile ?? 'No mobile') . "] " . ($dept ? " ($dept)" : '');
    }

    public static function addLocation($level, $name, $pin, $parent, $full = true)
    {
        $tmp = new PinCodes;
        $tmp->name = strtoupper(trim($name));
        $tmp->level = $level;
        $tmp->parent = $parent;
        $tmp->pincode = $pin;
        $tmp->save();

        if ($full) {
            $rec = array();
            $rec["id"] = $tmp->id;
            $rec["level"] = $tmp->level;
            $rec["name"] = $tmp->name;
            $rec["pincode"] = $tmp->pincode;
            $rec["parent"] = $tmp->parent;
            return $rec;
        } else
            return $tmp->id;
    }

    public static function getLocation($level, $name, $parent, $full = true)
    {
        $tmp = PinCodes::where('name', $name)->where('level', $level)->where('parent', $parent)->first();
        if ($tmp) {
            if ($full) {
                $rec = array();
                $rec["id"] = $tmp->id;
                $rec["level"] = $tmp->level;
                $rec["name"] = $tmp->name;
                $rec["pincode"] = $tmp->pincode;
                $rec["parent"] = $tmp->parent;
                return $rec;
            } else
                return $tmp->id;
        } else
            return false;
    }

    public static function locByPincode($pin)
    {
        $rex = PinCodes::where('pincode', $pin)->where('level', "POSTOFFICE")->get();
        //print_r($rex->toarray());
        if ($rex) {
            $po = array();
            foreach ($rex as $tmp) {
                $rec = array();
                $rec["id"] = $tmp->id;
                $rec["level"] = $tmp->level;
                $rec["name"] = $tmp->name;
                $rec["pincode"] = $tmp->pincode;
                $rec["parent"] = $tmp->parent;
                $po[] = $rec;
            }
            if (count($po) >= 1) {
                $tmp = PinCodes::find($po[0]["parent"]);
                $rec = array();
                $rec["id"] = $tmp->id;
                $rec["level"] = $tmp->level;
                $rec["name"] = $tmp->name;
                $rec["parent"] = $tmp->parent;
                $th = $rec;
                $tmp = PinCodes::find($th["parent"]);
                $rec = array();
                $rec["id"] = $tmp->id;
                $rec["level"] = $tmp->level;
                $rec["name"] = $tmp->name;
                $rec["parent"] = $tmp->parent;
                $dt = $rec;
                if ($th["name"] == "0" || empty($th["name"]))
                    $th["name"] = $dt["name"];
                $tmp = PinCodes::find($dt["parent"]);
                $rec = array();
                $rec["id"] = $tmp->id;
                $rec["level"] = $tmp->level;
                $rec["name"] = $tmp->name;
                $rec["parent"] = $tmp->parent;

                $drec = array("state" => $rec, "district" => $dt, "tehsil" => $th, "postoffice" => $po);
                return $drec;
            } else
                return false;
        }
    }

    public static function locById($id)
    {
        $tmp = PinCodes::find($id);
        //print_r($rex->toarray());
        if ($tmp && $tmp->level == "POSTOFFICE") {
            $rec = array();
            $rec["id"] = $tmp->id;
            $rec["level"] = $tmp->level;
            $rec["name"] = $tmp->name;
            $rec["pincode"] = $tmp->pincode;
            $rec["parent"] = $tmp->parent;
            $po = $rec;

            if (count($po) >= 1) {
                $tmp = PinCodes::find($po["parent"]);
                $rec = array();
                $rec["id"] = $tmp->id;
                $rec["level"] = $tmp->level;
                $rec["name"] = $tmp->name;
                $rec["parent"] = $tmp->parent;
                $th = $rec;
                $tmp = PinCodes::find($th["parent"]);
                $rec = array();
                $rec["id"] = $tmp->id;
                $rec["level"] = $tmp->level;
                $rec["name"] = $tmp->name;
                $rec["parent"] = $tmp->parent;
                $dt = $rec;
                if ($th["name"] == "0" || empty($th["name"]))
                    $th["name"] = $dt["name"];
                $tmp = PinCodes::find($dt["parent"]);
                $rec = array();
                $rec["id"] = $tmp->id;
                $rec["level"] = $tmp->level;
                $rec["name"] = $tmp->name;
                $rec["parent"] = $tmp->parent;

                $drec = array("state" => $rec, "district" => $dt, "tehsil" => $th, "postoffice" => $po);
                return $drec;
            } else
                return false;
        } else
            return false;
    }

    public static function getSubLocations($level, $name, $pin, $parent)
    {
        if ($level != "POSTOFFICE")
            $tmp = PinCodes::where('name', $name)->where('level', $level)->where('parent', $parent)->first();
        else
            return false;
        if ($tmp) {
            if ($level != "TEHSIL")
                $rex = PinCodes::where('parent', $tmp->id)->get();
            else
                $rex = PinCodes::where('parent', $tmp->id)->where('pincode', $pin)->get();
            if ($rex) {
                $data = array();
                foreach ($rex as $tmp) {
                    $rec = array();
                    $rec["id"] = $tmp->id;
                    $rec["level"] = $tmp->level;
                    $rec["name"] = $tmp->name;
                    $rec["pincode"] = $tmp->pincode;
                    $rec["parent"] = $tmp->parent;
                    $data[] = $rec;
                }
                if (count($data) >= 1)
                    return $data;
                else
                    return false;
            }
        }
        return false;
    }

    public static function fetchGlobalKey($key)
    {
        $res = Settings::select('data_key', 'value', 'details')->where('data_key', strtoupper($key))->first()->toArray();
        if ($res)
            return $res;
        else
            return false;
    }

    public static function getGlobals()
    {
        $res = Settings::select('data_key', 'value', 'details')->get()->toArray();
        if ($res)
            return $res;
        else
            return false;
    }

    public static function getDifference($n1, $n2, $abs = false)
    {
        $n3 = $n1 - $n2;
        if (($n3 < 0) && $abs)
            $n3 *= -1;
        return $n3;
    }

    public static function getUpdateSettings()
    {
        $ud = array();
        $ud["br"] = array("lbl" => "BRANCH_UPDATE", "sql" => null, "php" => null);
        $ud["vh"] = array("lbl" => "VHICLE_UPDATE", "sql" => null, "php" => null);
        $ud["op"] = array("lbl" => "PRICING_UPDATE", "sql" => null, "php" => null);
        $ud["rt"] = array("lbl" => "RTO_RULES_UPDATE", "sql" => null, "php" => null);
        $ud["in"] = array("lbl" => "INSURANCE_RULES_UPDATE", "sql" => null, "php" => null);
        $ud["cl"] = array("lbl" => "COLOR_UPDATE", "sql" => null, "php" => null);
        $ud["ap"] = array("lbl" => "APACK_UPDATE", "sql" => null, "php" => null);
        $ud["rs"] = array("lbl" => "RSA_UPDATE", "sql" => null, "php" => null);
        $ud["sh"] = array("lbl" => "SHIELD_UPDATE", "sql" => null, "php" => null);
        $ud["cr"] = array("lbl" => "CORP_BONUS_UPDATE", "sql" => null, "php" => null);
        $ud["xh"] = array("lbl" => "XCHANGE_BONUS_UPDATE", "sql" => null, "php" => null);
        $ud["sy"] = array("lbl" => "SYSTEM_UPDATE", "sql" => null, "php" => null);


        //Carbon::parse()->format('d/m/Y H:i:s');
        $tmp = Branches::select('created_at', 'updated_at')->orderby("updated_at", "DESC")->first();
        if ($tmp) {
            $ud["br"]["sql"] = $tmp->updated_at;
            $ud["br"]["php"] = Carbon::parse($tmp->updated_at)->format('d/m/Y H:i:s');
        }
        $tmp = Vehicle::select('created_at', 'updated_at')->orderby("updated_at", "DESC")->first();
        if ($tmp) {
            $ud["vh"]["sql"] = $tmp->updated_at;
            $ud["vh"]["php"] = Carbon::parse($tmp->updated_at)->format('d/m/Y H:i:s');
        }
        $tmp = PriceList::select('created_at', 'updated_at')->orderby("updated_at", "DESC")->first();
        if ($tmp) {
            $ud["op"]["sql"] = $tmp->updated_at;
            $ud["op"]["php"] = Carbon::parse($tmp->updated_at)->format('d/m/Y H:i:s');
        }

        $updated_at = RTOHelper::getUpdate();
        if ($updated_at) {
            $ud["rt"]["sql"] = $updated_at;
            $ud["rt"]["php"] = Carbon::parse($updated_at)->format('d/m/Y H:i:s');
        }
        $updated_at = InsuranceHelper::getUpdate();
        if ($updated_at) {
            $ud["in"]["sql"] = $updated_at;
            $ud["in"]["php"] = Carbon::parse($updated_at)->format('d/m/Y H:i:s');
        }
        $updated_at = ColorHelper::getUpdate();
        if ($updated_at) {
            $ud["cl"]["sql"] = $updated_at;
            $ud["cl"]["php"] = Carbon::parse($updated_at)->format('d/m/Y H:i:s');
        }

        $updated_at = ExtrasHelper::getApkUpdate();
        if ($updated_at) {
            $ud["ap"]["sql"] = $updated_at;
            $ud["ap"]["php"] = Carbon::parse($updated_at)->format('d/m/Y H:i:s');
        }
        $updated_at = ExtrasHelper::getRsaUpdate();
        if ($updated_at) {
            $ud["rs"]["sql"] = $updated_at;
            $ud["rs"]["php"] = Carbon::parse($updated_at)->format('d/m/Y H:i:s');
        }
        $updated_at = ExtrasHelper::getShlUpdate();
        if ($updated_at) {
            $ud["sh"]["sql"] = $updated_at;
            $ud["sh"]["php"] = Carbon::parse($updated_at)->format('d/m/Y H:i:s');
        }
        $updated_at = ExtrasHelper::getCrpUpdate();
        if ($updated_at) {
            $ud["cr"]["sql"] = $updated_at;
            $ud["cr"]["php"] = Carbon::parse($updated_at)->format('d/m/Y H:i:s');
        }
        $updated_at = ExtrasHelper::getXchUpdate();
        if ($updated_at) {
            $ud["xh"]["sql"] = $updated_at;
            $ud["xh"]["php"] = Carbon::parse($updated_at)->format('d/m/Y H:i:s');
        }
        $max = null;
        foreach ($ud as $mkey => $md) {
            if ($md["sql"] != null) {
                if ($max == null)
                    $max = Carbon::parse($md["sql"]);
                else {
                    $cd = Carbon::parse($md["sql"]);
                    $max = ($max->gt($cd)) ? $max : $cd;
                }
                self::addGlobalKey($md["lbl"], $md["php"]);
            }
        }
        //print_r($max);
        if ($max) {
            $ud["sy"]["sql"] = Carbon::parse($max)->format('Y-m-d H:i:s');
            $ud["sy"]["php"] = Carbon::parse($max)->format('d/m/Y H:i:s');
            self::addGlobalKey($ud["sy"]["lbl"], $ud["sy"]["php"]);
        }
        //dd($ud);
        return $ud;
    }

    public static function getUserData($data, $kw)
    {
        $data = strtoupper($data);
        $kw = strtoupper($kw);
        $mast = self::GetKeyValues($kw);
        //print_r("<br>In CH::getUserData looking for $data of $kw and here is master :");
        //print_r($mast);
        $rtval = "";
        if ($data == "ALL") {
            foreach ($mast['byvalue'] as $key => $value) {
                if ($rtval == "")
                    $rtval = $value;
                else
                    $rtval .= "," . $value;
            }
        } elseif (strpos($data, ",") >= 0) {
            $darr = explode(",", $data);
            foreach ($darr as $dit) {
                $flag = 0;
                foreach ($mast['bykey'] as $key => $value) {

                    if ($value == $dit) {
                        if ($rtval == "")
                            $rtval = $key;
                        else
                            $rtval .= "," . $key;
                        $flag++;
                    }
                }
                if ($flag == 0) {
                    $nr = self::getOrCreateEnumId($dit, $kw);
                    if ($rtval == "")
                        $rtval = $nr;
                    else
                        $rtval .= "," . $nr;
                }
                $flag = 0;
            }
        } else {
            foreach ($mast['bykey'] as $key => $value)
                if ($value == $data)
                    $rtval = $key;
        }
        return $rtval;
    }

    public static function getUserModels($segs, $models)
    {
        print_r("<br>In CH::getUserModel :");
        print_r($segs);
        $models = strtoupper($models);
        $segs = self::getUserData($segs, "SEGMENT");
        //print_r("<br>In CH::getUserModel :");
        //print_r($segs);
        $mm = VehicleHelper::getCustomModel(false, $segs);
        //print_r("<br>BAck In CH::getUserModel here is MM :");
        //print_r($mm);
        $rtval = "";
        if ($models == "ALL") {
            if (strpos($segs, ",") >= 0) {
                $sar = explode(",", $segs);
                foreach ($sar as $stm) {
                    if ($rtval == "")
                        $rtval = implode(",", $mm['byseg'][$stm]);
                    else
                        $rtval .= "," . implode(",", $mm['byseg'][$stm]);
                }
            } else
                $rtval = $mm['byseg'][$segs];
        } elseif (strpos($models, ",") >= 0) {
            $mar = explode(",", $models);
            foreach ($mar as $mtm) {
                foreach ($mm['bykey'] as $key => $value) {
                    if ($value == $mtm) {
                        if ($rtval == "")
                            $rtval = $key;
                        else
                            $rtval .= "," . $key;
                    }
                }
            }
        }

        return $rtval;
    }




    public static function format_row($row)
    {
        foreach ($row as $key => $val) {
            $val = $row[$key] = strtoupper(trim(str_replace(',', '', $val)));
            if (empty($val))
                $row[$key] = 0;
            elseif ($val == "NA" || $val == "-NA-" || $val == "---")
                $row[$key] = 0;
            elseif (is_numeric($val))
                $row[$key] = round($val, 2);
        }
        return $row;
    }

    public static function isPLEnable()
    {
        $bdata = Branches::where('parent', 0)->first();
        if ($bdata->pricelist_active == 1)
            return true;
        else
            return false;
    }

    public static function isColEmpty($aiq, $key)
    {
        $flag = true;
        foreach ($aiq as $row) {
            if (!empty($row[$key]))
                $flag = false;
        }
        return $flag;
    }
    /* if(is_array($row[$key]))
			{
			if(sizeof($row[$key]) != 0)
			$flag = false;
			}
		else */

    public static function empByDesigDepart($desig, $dept)
    {
        $ulist = User::select('id', 'role', 'designation', 'department', 'branch', 'location', 'segment')->get()->toArray();
        $fulist = array();
        foreach ($ulist as $urec) {
            if (in_array($urec['designation'], $desig)) {
                if (!empty($dept) || $dept == null)
                    $fulist[] = $urec['id'];
                else {
                    $udept = explode(",", $urec['department']);
                    foreach ($dept as $cdept) {
                        if (in_array($cdept, $udept))
                            $fulist[] = $urec['id'];
                    }
                }
            }
        }
        return $fulist;
    }

    public static function getAllEmp()
    {
        $ulist = User::select('id', 'role')->get()->toArray();
        $fulist = array();
        foreach ($ulist as $urec)
            $fulist[] = $urec['id'];
        return $fulist;
    }

    public static function slugify($text)
    {
        return Str::slug($text);
    }

    public static function addDesigDepart($desig, $depart)
    {
        DesignationDepartment::updateOrCreate(['designation_id' => $desig, 'department_id' => $depart]);
        return;
    }

    public static function remDesDepByDesig($desig)
    {
        DesignationDepartment::where('designation_id', $desig)->forceDelete();
        return;
    }

    public static function remDesDepByDepart($depart)
    {
        DesignationDepartment::where('department_id', $depart)->forceDelete();
        return;
    }

    public static function userBase()
    {
        $ulist = User::select('id', 'emp_code', 'person_id', 'email', 'mobile', 'designation', 'department', 'branch', 'location', 'segment', 'vertical')->with("person")->get();
        $data = array();
        foreach ($ulist as $urec) {
            $row = array();
            $row['id'] = $urec->id;
            $row['emp_code'] = $urec->emp_code;
            $row['name'] = $urec->person->firstname;
            $row['email'] = $urec->email;
            $row['mobile'] = $urec->mobile;
            $row['role'] = $urec->get_roles();
            $row['designation'] = $urec->get_designation();
            $row['department'] = $urec->get_departments();
            $row['branch'] = $urec->get_branches();
            $row['location'] = $urec->get_locations();
            $row['segment'] = $urec->get_segments();
            $row['vertical'] = $urec->get_verticals();
            $data[] = $row;
        }
        return $data;
    }

    public static function getUserByFilters($desig, $departs)
    {
        // print_r("<BR> Desig : $desig, Departs : $departs <BR>");
        if (!empty($desig)) {
            $data = $mdata = array();
            if (!is_array($desig)) {
                $tmp = explode("@@", $desig);
                $desig = $tmp;
            }
            if (!is_array($departs)) {
                $tmp = explode("||", $departs);
                $departs = $tmp;
            }
            foreach ($desig as $desi) {
                $tmp = User::select("id", "name", "person_id", "mobile", "department")->where('designation', $desi)->get()->toArray();
                $deptU = $users = array();

                if ($tmp) {
                    foreach ($tmp as $urc) {
                        $urc["department"] = explode(",", $urc["department"]);
                        foreach ($urc["department"] as $dpt) {
                            if (!isset($deptU[$dpt]))
                                $deptU[$dpt] = array($urc["id"]);
                            else
                                $deptU[$dpt][] = $urc["id"];
                        }
                        $users[$urc["id"]] = $urc;
                    }
                    //dd($users);
                    foreach ($departs as $itm) {
                        if (isset($deptU[$itm])) {
                            foreach ($deptU[$itm] as $uid) {
                                if (isset($users[$uid]))
                                    $data[$uid] = $users[$uid];
                            }
                        }
                    }
                    // dd($data);
                }
            }
            return $data;
        } else {
            return User::select("id", "name", "person_id", "mobile", "department")->get()->toArray();
        }
    }


    public static function getDesigDepart($desig)
    {
        $tmp = DesignationDepartment::where('designation_id', $desig)->get();
        if ($tmp) {
            $departs = array();
            foreach ($tmp as $itm) {
                $dname = self::enumValueById($itm->department_id);
                $departs[] = array('id' => $itm->department_id, 'name' => $dname);
            }
            return $departs;
        } else
            return false;
    }

    public static function getDepartDesig($depart)
    {
        $tmp = DesignationDepartment::where('department_id', $depart)->get();
        if ($tmp) {
            $desigs = array();
            foreach ($tmp as $itm) {
                $dname = self::enumValueById($itm->designation_id);
                $desigs[] = array('id' => $itm->designation_id, 'name' => $dname);
            }
            return $desigs;
        } else
            return false;
    }



    public static function ExportStyleCommon()
    {
        return [
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'color' => ['argb' => 'FFCCCC']
            ],
            'font' => [
                'bold'  =>  false,
                'size'  =>  8,
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => 'thin',
                    'color' => ['rgb' => '808080']
                ],
                'outline' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK,
                    'color' => ['argb' => 'FFFF0000'],
                ],

            ],
        ];
    }

    public static function ExportStyleHeader()
    {
        return [
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'color' => ['argb' => 'FFA31A']
            ],
            'font' => [
                'bold'  =>  true,

            ],
            'alignment' => [

                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ],
        ];
    }

    public static function ExportStyleOpen()
    {
        return [
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'color' => ['argb' => 'FFFF00']
            ],
        ];
    }

    public static function ExportStyleDivider()
    {
        return [
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'color' => ['argb' => '000000']
            ],
        ];
    }

    public static function ExportStyleFormula()
    {
        return [
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'color' => ['argb' => 'C2F0C2']
            ],
            'font' => [
                'bold'  =>  true,

            ],
        ];
    }

    public static function ExportStyleApproval()
    {
        return [
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'color' => ['argb' => 'C8FA98']
            ],
        ];
    }

    /// enumValueById($id) :: Get any ENUM value by Id
    public static function enumValueById($id)
    {
        //print_r("<br><br><br>Searching for $id in enumValueById<br><br>");
        $tmp = null;
        $tmp = EnumMaster::select('id', 'value')->where('id', $id)->first();
        if ($tmp)
            return $tmp->value;
        else
            return null;
    }

    public static function getSubSegID($sseg)
    {
        $mid = Enumrator::select('id', 'name', 'details')->where('keyword', "SEGMENT")->first();
        $tmp = EnumMaster::select('id', 'value', 'parent_id')->where('master_id', $mid->id)->where('parent_id', '>', 0)->where('value', $sseg)->first();
        if ($tmp)
            return $tmp->id;
        else
            return false;
    }

    public static function getOrCreateSubSegID($sseg, $sid)
    {
        //\Log::info('Creating or fetching SubSegment ID for : ' . $sseg . ', Parent ID : ', [$sid]);
        $mid = Enumrator::select('id', 'name', 'details')->where('keyword', "SEGMENT")->first();
        $tmp = EnumMaster::select('id', 'value', 'parent_id')->where('master_id', $mid->id)->where('parent_id', $sid)->where('value', $sseg)->first();
        if ($tmp) {
            return $tmp->id;
            //\Log::info('SubSeg Found : ', $tmp->toarray());
        } else {
            //\Log::info('SubSeg Not Found ');
            $tmp = EnumMaster::create([
                "master_id" => $mid->id,
                "parent_id" => $sid,
                "value" => $sseg,
                "status" => 1
            ]);
            return $tmp->id;
        }
    }

    public static function getAllOsTRC()
    {
        //master_id = 65;
        $res = EnumMaster::select('id', 'value', 'parent_id')->where('master_id', 65)->get();
        if ($res) {
            $data = array();
            foreach ($res as $dat) {
                $data[$dat->parent_id] = array('permit_id' => $dat->parent_id, 'permit' => self::enumValueById($dat->parent_id), 'trc' => $dat->value);
            }
            return $data;
        } else
            return false;
    }

    public static function getOsTRCByPermit($pid)
    {
        //master_id = 65;
        $res = EnumMaster::select('id', 'value', 'parent_id')->where('master_id', 65)->where('parent_id', $pid)->first();
        if ($res)
            return $res->value;
        else
            return false;
    }

    public static function getDiscountModes()
    {
        //master_id = 65;
        $res = EnumMaster::select('id', 'value', 'val_type')->where('master_id', 68)->get();
        $data = array();
        foreach ($res as $rec)
            $data[$rec->value] = array('name' => $rec->value, 'mode' => $rec->val_type);
        return $data;
    }

    public static function getSegID($seg)
    {
        $mid = Enumrator::select('id', 'name', 'details')->where('keyword', "SEGMENT")->first();
        $tmp = EnumMaster::select('id', 'value', 'parent_id')->where('master_id', $mid->id)->where('parent_id', 0)->where('value', $seg)->first();
        if ($tmp)
            return $tmp->id;
        else
            return false;
    }


    public static function getFnSId($kw, $val, $parent = 0)
    {
        $tmp = EnumMaster::updateOrCreate(
            [
                "master_id" => $kw,
                "parent_id" => $parent,
                "value" => $val
            ],
            [
                "status" => 1
            ]
        );
        return $tmp->id;
    }

    public static function getDesignations()
    {
        $data = self::enumGetValues("DESIGNATION");
        $desigs = array();
        foreach ($data as $itm) {
            $desigs[] = array('id' => $itm['id'], 'parent' => $itm['parent_id'], 'designation' => $itm['value']);
        }
        return $desigs;
    }

    public static function getDesignation($id)
    {
        $data = EnumMaster::where('id', $id)->first();
        if ($data) {
            return $data;
        } else
            return false;
    }

    public static function getDepartments()
    {
        $data = self::enumGetValues("DEPARTMENT");
        $desigs = array();
        foreach ($data as $itm) {
            $desigs[] = array('id' => $itm['id'], 'department' => $itm['value']);
        }
        return $desigs;
    }

    public static function AddonTaxRate($value)
    {
        self::getOrCreateEnumId($value, "ADDON_TAX_RATE");
        return;
    }
    /// enumGetValues($tbl,$col,$parent) :: Get list of options with id,value for any enum field i.e. enumGetValues('MakeModel','subsegment',2) will give list of subsegments under Commercial

    public static function enumGetValues($kw, $tbl = NULL, $col = NULL, $parent = 0, $lvl = 0)
    {
        if ($kw) {
            $mid = Enumrator::select('id', 'name', 'details')->where('keyword', strtoupper($kw))->first();
        } elseif ($col && $tbl) {
            $mid = Enumrator::select('id', 'name', 'details')->where('tbl_name', strtolower($tbl))->where('col_name', strtolower($col))->first();
        } else
            $mid = NULL;
        $tmp = null;
        //print_r("<br>Enum Master ID :"); //print_r($mid->id);
        if ($mid) {
            if ($parent == 0) {
                if ($lvl == 0) {
                    //print_r("<br>Getting 1 :");
                    $tmp = EnumMaster::select('id', 'value', 'parent_id', 'recursion_level', 'val_type', 'status')->where('master_id', $mid->id)->get();
                } else {
                    //print_r("<br>Getting 2 :");
                    $tmp = EnumMaster::select('id', 'value', 'parent_id', 'recursion_level', 'val_type', 'status')->where('master_id', $mid->id)->where('recursion_level', $lvl)->get();
                }
            } else {
                if ($lvl == 0) {
                    //print_r("<br>Getting 3 :");
                    $tmp = EnumMaster::select('id', 'value', 'parent_id', 'recursion_level', 'val_type', 'status')->where('master_id', $mid->id)->where('parent_id', $parent)->get();
                } else {
                    //print_r("<br>Getting 4 :");
                    $tmp = EnumMaster::select('id', 'value', 'parent_id', 'recursion_level', 'val_type', 'status')->where('master_id', $mid->id)->where('parent_id', $parent)->where('recursion_level', $lvl)->get();
                }
            }
        }
        if ($tmp) {
            $retval = array();
            foreach ($tmp as $itm)
                $retval[] = $itm->toArray();
            return $retval;
        } else
            return null;
    }

    public static function enumGetValuesList($kw, $parent = 0, $lvl = 0)
    {
        if ($kw) {
            $mid = Enumrator::select('id', 'name', 'details')->where('keyword', strtoupper($kw))->first();
        }

        $tmp = null;
        if ($mid) {
            if ($parent == 0)
                $tmp = EnumMaster::select('id', 'value')->where('master_id', $mid->id)->where('recursion_level', $lvl)->get();
            else
                $tmp = EnumMaster::select('id', 'value')->where('master_id', $mid->id)->where('parent_id', $parent)->get();
        }
        if ($tmp) {
            $retval = array();
            foreach ($tmp as $itm)
                $retval[] = $itm->value;
            return $retval;
        } else
            return null;
    }

    public static function enumGetKeyValues($kw)
    {
        if ($kw) {
            $mid = Enumrator::select('id', 'name', 'details')->where('keyword', strtoupper($kw))->first();
        }

        $tmp = null;
        if ($mid) {

            $tmp = EnumMaster::select('id', 'value')->where('master_id', $mid->id)->get();
        }
        if ($tmp) {
            $retval = array();
            foreach ($tmp as $itm)
                $retval[$itm->value] = $itm->id;
            return $retval;
        } else
            return null;
    }

    public static function GetKeyValues($kw)
    {
        if ($kw) {
            $mid = Enumrator::select('id', 'name', 'details')->where('keyword', strtoupper($kw))->first();
        }

        $tmp = null;
        if ($mid) {

            $tmp = EnumMaster::select('id', 'value')->where('master_id', $mid->id)->where('status', 1)->get();
        }
        if ($tmp) {
            $retval = array('bykey' => array(), 'byvalue' => array());
            foreach ($tmp as $itm) {
                $retval['byvalue'][$itm->value] = $itm->id;
                $retval['bykey'][$itm->id] = $itm->value;
            }
            return $retval;
        } else
            return null;
    }

    public static function updateCM($id, $val, $parent)
    {
        $tmp = EnumMaster::find($id);
        if (!$tmp) {
            return false;
        } else {
            $tmp->parent_id = $parent;
            $tmp->val_type = $val;
            $tmp->save();
        }
    }

    public static function getCMG($name)
    {
        $tmp = EnumMaster::where('master_id', 67)->where('value', $name)->first();
        if (!$tmp) {
            $tmp = new EnumMaster;
            $tmp->master_id = 67;
            $tmp->value = strtoupper($name);
            $tmp->save();
        }
        return $tmp->id;
    }

    public static function getAllCMG()
    {
        $tmp = EnumMaster::where('master_id', 67)->get();
        $data = false;
        if ($tmp) {
            $data = array();
            foreach ($tmp as $mg)
                $data[$mg->id] = $mg->value;
        }
        return $data;
    }



    public static function createCM($name, $cmgid, $cmg) // return id if created, false if duplicate with other parent, true if exist with same parent
    {
        $name = strtoupper($name);
        $cmg = strtoupper($cmg);
        $tmp = EnumMaster::where('master_id', 47)->where('value', $name)->first();
        //dd($tmp->toarray());

        if ($tmp) {
            if ($tmp->parent_id != $cmgid)
                return false;
            else
                return true;
        } else {
            $tmp = new EnumMaster;
            $tmp->master_id = 47;
            $tmp->value = $name;
            $tmp->parent_id = $cmgid;
            $tmp->val_type = $cmg;
            $tmp->save();
        }
        return $tmp->id;
    }

    public static function getCM($pid = null)
    {
        if ($pid == null)
            $tmp = EnumMaster::where('master_id', 47)->orderby('parent_id')->get();
        else
            $tmp = EnumMaster::where('master_id', 47)->where('parent_id', $pid)->get();
        $cms = array();
        if (!$tmp) {
            return false;
        } else {

            foreach ($tmp as $cm) {
                $cms[$cm->id] = array('id' => $cm->id, 'name' => $cm->value, 'mgid' => $cm->parent_id, 'mg' => $cm->val_type);
            }
            return $cms;
        }
    }


    public static function updateEnumValByID($id, $val)
    {
        $tmp = EnumMaster::find($id);
        if (!$tmp) {
            return false;
        } else {
            $tmp->value = $val;
            $tmp->save();
            return $tmp->toarray();
        }
    }

    public static function updtFHeadBoundings($on, $nn)
    {
        EnumMaster::where('master_id', 26)->where('val_type', $on)->update(array('val_type' => $nn));
        return;
    }


    public static function getFnSHeads($val, $bounded, $type, $parent = 0)
    {
        $tmp = EnumMaster::updateOrCreate([
            'master_id' => $type,
            'value' => $val,
            'parent_id' => $parent,
            'val_type' => $bounded
        ]);
        if ($tmp)
            return $tmp->id;
        else
            return false;
    }

    public static function delFnSHeads($kw, $cm)
    {
        EnumMaster::where('master_id', $kw)->where('val_type', $cm)->forceDelete();
        return;
    }

    public static function fetchFnSHeads($mid, $bounded)
    {
        //print_r("Fetching Headings for $mid, $bounded");
        $hdata = EnumMaster::where('master_id', $mid)->where('val_type', $bounded)->orderby('id')->get();
        //dd($hdata->toArray());
        $data = array();
        foreach ($hdata as $item) {
            if ($item->parent_id == 0) {
                if (!isset($data[$item->id]))
                    $data[$item->id] = array('id' => $item->id, 'name' => $item->value, 'child' => array());
            } else {
                if (!isset($data[$item->id]))
                    $data[$item->parent_id]['child'][$item->id] = array('id' => $item->id, 'name' => $item->value);
            }
        }
        //dd($data);
        return $data;
    }


    public static function getOrCreateEnumId($val, $kw, $parent = 0,   $details = "NA")
    {
        //print_r("<br>Searching for $kw  = $val");
        if ($kw) {
            //print_r("<br>Found Keyword : $kw<br>");
            $mid = Enumrator::select('id', 'name', 'details')->where('keyword', strtoupper($kw))->first();
        } else {
            $mid = NULL;
        }
        //print_r("<br>MID :<br>");
        //print_r($mid);
        $tmp = null;
        if ($mid == NULL) {
            if ($kw) {
                $mid = new Enumrator;
                $mid->keyword = strtoupper($kw);
                $mid->details = $details;
                $mid->status = 1;
                $mid->save();
            }
        }
        if ($mid) {
            if ($parent == 0)
                $tmp = EnumMaster::select('id', 'value', 'master_id')->where('master_id', $mid->id)->where('value', $val)->first();
            else
                $tmp = EnumMaster::select('id', 'value', 'master_id')->where('master_id', $mid->id)->where('parent_id', $parent)->where('value', $val)->first();
            if (!$tmp) {
                $tmp = new EnumMaster;
                $tmp->master_id = $mid->id;
                $tmp->value = $val;
                $tmp->parent_id = $parent;
                $tmp->status = 1;
                $tmp->save();
            }
        }
        //print_r("<br>TEMP :<br>");
        //print_r($tmp->id);
        if ($tmp)
            return $tmp->id;
        else
            return null;
    }

    public static function updateFirst($kw, $val, $parent = 0)
    {
        $mid = Enumrator::select('id', 'name', 'details')->where('keyword', strtoupper($kw))->first();
        if ($mid) {
            if ($parent == 0)
                $tmp = EnumMaster::where('master_id', $mid->id)->first();
            else
                $tmp = EnumMaster::where('master_id', $mid->id)->where('parent_id', $parent)->first();
            if (!$tmp) {
                $tmp = new EnumMaster;
                $tmp->master_id = $mid->id;
                $tmp->value = $val;
                $tmp->parent_id = $parent;
                $tmp->status = 1;
                $tmp->save();
            } else {
                $tmp->value = $val;
                $tmp->save();
            }
        }
        return $tmp->toArray();
    }

    public static function updatePLStepStatus($stepno, $val)
    {
        //Val :: "1-Completed, 2-Pending, 3-Ignored
        if (strtoupper($val) == "COMPLETED")
            $sval = 1;
        elseif (strtoupper($val) == "PENDING")
            $sval = 2;
        elseif (strtoupper($val) == "IGNORED")
            $sval = 3;
        $mid = Enumrator::select('id', 'name', 'details')->where('keyword', "PL_CL_HEAD")->first();
        if ($mid) {
            $steps = EnumMaster::where('master_id', $mid->id)->get();
            //print_r($steps->toarray());
            foreach ($steps as $step) {
                //print_r("<br>Processing Step  # ".$step->recursion_level);
                if ($stepno == 0 || $stepno == $step->recursion_level) {
                    //print_r("<br>Matched");
                    $step->status = $sval;
                    //print_r("<br>Updated to : ");
                    $step->save();
                    //print_r($step->status);
                }
            }
        }
    }

    public static function getFirst($kw, $parent = 0)
    {
        $mid = Enumrator::select('id', 'name', 'details')->where('keyword', strtoupper($kw))->first();
        if ($mid) {
            if ($parent == 0)
                $tmp = EnumMaster::where('master_id', $mid->id)->first();
            else
                $tmp = EnumMaster::where('master_id', $mid->id)->where('parent_id', $parent)->first();
            if (!$tmp)
                return false;
            else
                return $tmp->toArray();
        } else
            return false;
    }

    /// enumIdByValue($val,$tbl,$col) :: Get any enum id by value
    public static function enumIdByValue($val, $kw, $tbl = NULL, $col = NULL)
    {
        if ($kw) {
            $mid = Enumrator::select('id', 'name', 'details')->where('keyword', strtoupper($kw))->first();
        } elseif ($col && $tbl) {
            $mid = Enumrator::select('id', 'name', 'details')->where('tbl_name', strtolower($tbl))->where('col_name', strtolower($col))->first();
        } else {
            $mid = NULL;
        }

        $tmp = null;
        if ($mid) {
            $tmp = EnumMaster::select('id', 'value')->where('master_id', $mid->id)->where('value', $val)->first();
        }
        if ($tmp)
            return $tmp->id;
        else
            return null;
    }


    public static function decodeParams($details, $type)
    {
        if ($type == "INS") {
            $fvals = array("zone" => "ANY", "zone_cat" => "ANY", "cm" => "ANY", "inscomp" => "ANY", "permit" => "ANY", "ctype" => "ANY", "fuel" => "ANY", "cc" => "ANY", "seating" => "ANY", "wheels" => "ANY");
            $porder = array('zone', 'zone_cat', 'cm', 'inscomp', 'permit', 'ctype', 'fuel', 'cc', 'seating', 'wheels');
        } elseif ($type == "RTO") {
            $fvals = array('state_id' => "ANY", 'bodymake_id' => "ANY", 'fuel_id' => "ANY", 'permit_id' => "ANY", 'seat' => "ANY", 'cc' => "ANY", 'wheels' => "ANY", 'weight' => "ANY");
            $porder = array('state_id', 'bodymake_id', 'fuel_id', 'permit_id', 'seat', 'cc', 'wheels', 'weight');
            //state,bodytype,fuel,permit,seat,cc,wheels,weight
        }
        $params = explode(",", $details);
        //print_r("<br>PARam as Array : <br>");
        //print_r($params);
        //print_r("<br>");
        $cnt = 0;
        foreach ($params as $val) {
            $chead = $porder[$cnt];
            if (is_numeric($val) && $val == 0) {
                //print_r("<br>In Zero : $val");

                $fvals[$chead] = "ANY";
            } else {
                //print_r("<br>In Value : $val");
                $val = self::dConvert($val);

                if ($chead == "permit" || $chead == "ctype" || $chead == "fuel" || $chead == "zone_cat") {
                    $fvals[$chead] = self::enumValueById($val);
                } elseif ($chead == "inscomp") {
                    $fvals[$chead] = InsuranceHelper::getInsCompSingle($val);
                } else {
                    $fvals[$chead] = $val;
                }
            }
            $cnt++;
        }
        return $fvals;
    }

    public static function dConvert($val)
    {
        //print_r("<br>Decoding Value : $val");
        if (substr_count($val, "-") == 1) {
            $tmp = explode(" ", $val);
            $rtval = $tmp[1] . " - " . $tmp[2];
        } elseif (is_numeric($val) &&  $val == 0) {
            $rtval = "ANY";
        } else
            $rtval = $val;
        //print_r(" === $rtval");
        return $rtval;
    }


    public static function addPlTnc($eng, $hin)
    {
        $engid = self::getOrCreateEnumId("ENGLISH", "LANGUAGE");
        $hinid = self::getOrCreateEnumId("HINDI", "LANGUAGE");
        $mid = Enumrator::select('id', 'name', 'details')->where('keyword', 'PL-TNC')->first();
        $tmp = new EnumMaster;
        $tmp->master_id = $mid->id;
        $tmp->value = $eng;
        $tmp->parent_id = 0;
        $tmp->val_type = $engid;
        $tmp->status = 1;
        $tmp->save();
        $eid = $tmp->id;
        $tmp = new EnumMaster;
        $tmp->master_id = $mid->id;
        $tmp->value = $hin;
        $tmp->parent_id = $eid;
        $tmp->val_type = $hinid;
        $tmp->status = 1;
        $tmp->save();
    }




    public static function getPlSource()
    {
        $data = PlSource::get();
        $rtdata = array();
        foreach ($data as $row) {
            $rtdata[self::enumValueById($row->type_id)][$row->branch_id]["name"] = strtoupper($row->name);
            $rtdata[self::enumValueById($row->type_id)][$row->branch_id]["id"] = strtoupper($row->id);
        }
        return $rtdata;
    }


    public static function createCMID()
    {
        $cmar = Vehicle::where('head_type', '>', 2)->get();
        //print_r($cmar);
        foreach ($cmar as $cm) {
            if ($cm->cm1 != "") {
                $cm->cm_id = self::getOrCreateEnumId($cm->cm1, 'CUSTOM-MODEL');
                $cm->save();
            }
        }
    }

    /// enumValueById($id) :: Get any ENUM value by Id
    public static function getSyncStatus()
    {
        $sst = array();
        $data = self::enumGetValues("UPDATE_DATE");
        foreach ($data as $row) {
            $sst[$row["val_type"]] = array("id" => $row['id'], "title" => $row['val_type'], "timestamp" => $row['value']);
        }
        return $sst;
    }

    public static function getPermitTypes()
    {
        $rdata = array();
        $data = self::enumGetValues("PERMIT");
        foreach ($data as $row) {
            $rdata[$row["value"]] = $row["id"];
        }
        return $rdata;
    }

    public static function getCarrierTypes()
    {
        $rdata = array();
        $data = self::enumGetValues("CARRIER-TYPE");
        foreach ($data as $row) {
            $rdata[$row["value"]] = $row["id"];
        }
        return $rdata;
    }

    public static function GetRoles()
    {
        $roles = "";
        $rdata = Roles::where('id', '>', 1)->get();
        foreach ($rdata as $role) {
            if (strlen($roles) > 2) {
                $roles .= ", ";
            }
            $roles .= $role->name;
        }
        return $roles;
    }



    public static function addHistory($tbl, $col, $pk, $vi, $ov, $nv)
    {
        $hrec = new History;
        $hrec->table_name = $tbl;
        $hrec->column_name = $col;
        $hrec->old_value = $ov;
        $hrec->new_value = $nv;
        $hrec->rec_id = $pk;
        $hrec->vehicle_id = $vi;
        $hrec->save();
        return;
    }
}
