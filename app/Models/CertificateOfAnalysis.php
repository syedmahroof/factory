<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class CertificateOfAnalysis extends Model {
    use HasUuids;

    public function uniqueIds(): array { return ['uuid']; }

    protected $guarded = [];
    protected $table = 'certificates_of_analysis';
    public function inspection() { return $this->belongsTo(Inspection::class); }
    public function item() { return $this->belongsTo(Item::class); }
    public function issuedBy() { return $this->belongsTo(User::class, 'issued_by'); }
}