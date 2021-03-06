<?php
/* Copyright (C) 2002-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2003      Brian Fraval         <brian@fraval.org>
 * Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2008      Patrick Raguin       <patrick.raguin@auguria.net>
 * Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2018      Philippe Grand       <philippe.grand@atoo-net.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/custom/immobilier/class/renter.class.php
 *	\ingroup    immobilier
 *	\brief      File for renter class
 */
require_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");


/**
 *	Class to manage third parties objects (customers, suppliers, prospects...)
 */
class Renter extends Societe
{
    public $db;
    public $error;
    public $errors=array();
    public $element='societe';
    public $table_element = 'societe';
	
    /**
     * 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
     * @var int
     */
    protected $ismultientitymanaged = 1;

	public $entity;

    public $id;
	
    /**
     * Thirdparty name
     * @var string
     * @deprecated Use $name instead
     * @see name
     */
    public $nom;
	
    public $nom_particulier;
    public $firstname;
    public $particulier;
    public $address;
    public $zip;
    public $town;

    /**
     * Id of department
     * @var int
     */
    public $state_id;
	
	/**
     * State code
     * @var string
     */
    public $state_code;
    public $state;

    public $country_id;
    public $country_code;
    public $country;

   /**
	 * Phone number
	 * @var string
	 */
	public $phone;
	
	/**
	 * Fax number
	 * @var string
	 */
	public $fax;
	
	/**
	 * Email
	 * @var string
	 */
	public $email;
	
	/**
	 * Skype username
	 * @var string
	 */
	public $skype;
	
	/**
	 * Webpage
	 * @var string
	 */
	public $url;
	
	/**
     * Barcode value
     * @var string
     */
    public $barcode;

    // 6 professional id (usage depends on country)

    /**
     * Professional ID 1 (Ex: Siren in France)
     * @var string
     */
    public $idprof1;

	/**
     * Professional ID 2 (Ex: Siret in France)
     * @var string
     */	
    public $idprof2;	
	
	/**
     * Professional ID 3 (Ex: Ape in France)
     * @var string
     */
    public $idprof3;
	
	 /**
     * Professional ID 4 (Ex: RCS in France)
     * @var string
     */
    public $idprof4;

	/**
     * Professional ID 5
     * @var string
     */
    public $idprof5;

    /**
     * Professional ID 6
     * @var string
     */
    public $idprof6;	

    public $prefix_comm;

    public $tva_assuj;
	
	/**
     * Intracommunitary VAT ID
     * @var string
     */
    public $tva_intra;

    // Local taxes
    public $localtax1_assuj;
    public $localtax2_assuj;

    public $capital;
    public $typent_id;
    public $typent_code;
    public $effectif_id;
    public $forme_juridique_code;
    public $forme_juridique;

    public $remise_percent;
    public $mode_reglement_id;
    public $cond_reglement_id;

    public $client;					// 0=no customer, 1=customer, 2=prospect
    public $prospect;					// 0=no prospect, 1=prospect
    public $fournisseur;				// =0no supplier, 1=supplier

    public $code_client;
    public $code_fournisseur;
    public $code_compta;
    public $code_compta_fournisseur;

    public $note_public;
    public $note_private;
    //! code statut prospect
    public $stcomm_id;
    public $statut_commercial;

    public $price_level;

    public $datec;
    public $date_update;

    public $commercial_id; //Id du commercial affecte
    public $default_lang;

    public $canvas;

    public $import_key;

    public $logo;
    public $logo_small;
    public $logo_mini;


    /**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
     */
    public function __construct($db)
    {
        global $conf;

        $this->db = $db;

        $this->client = 0;
        $this->prospect = 0;
        $this->fournisseur = 0;
        $this->typent_id  = 0;
        $this->effectif_id  = 0;
        $this->forme_juridique_code  = 0;
        $this->tva_assuj = 1;

        return 1;
    }


    /**
     *  Update parameters of third party
     *
     *  @param     	int		$id              			id societe
     *  @param      string	$user            			Utilisateur qui demande la mise a jour
     *  @param      int		$call_trigger    			0=non, 1=oui
     *	@param		int		$allowmodcodeclient			Inclut modif code client et code compta
     *	@param		int		$allowmodcodefournisseur	Inclut modif code fournisseur et code compta fournisseur
     *  @return     int      			       			<0 si ko, >=0 si ok
     */
    function update($id, $user='', $call_trigger=1, $allowmodcodeclient=0, $allowmodcodefournisseur=0)
    {
        global $langs,$conf,$hookmanager;
        require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

		$error=0;

        dol_syslog(get_class($this)."::Update id=".$id." call_trigger=".$call_trigger." allowmodcodeclient=".$allowmodcodeclient." allowmodcodefournisseur=".$allowmodcodefournisseur);

        // Clean parameters
         $this->id			= $id;
        $this->name			= $this->name?trim($this->name):trim($this->nom);
         $this->address		= $this->address?trim($this->address):trim($this->address);
        $this->zip			= $this->zip?trim($this->zip):trim($this->zip);
        $this->town			= $this->town?trim($this->town):trim($this->town);
        $this->state_id		= trim($this->state_id);
        $this->country_id	= ($this->country_id > 0)?$this->country_id:0;
        $this->phone		= trim($this->phone);
        $this->phone		= preg_replace("/\s/","",$this->phone);
        $this->phone		= preg_replace("/\./","",$this->phone);
        $this->fax			= trim($this->fax);
        $this->fax			= preg_replace("/\s/","",$this->fax);
        $this->fax			= preg_replace("/\./","",$this->fax);
        $this->email		= trim($this->email);
        $this->skype		= trim($this->skype);
        $this->url			= $this->url?clean_url($this->url,0):'';
        $this->idprof1		= trim($this->idprof1);
        $this->idprof2		= trim($this->idprof2);
        $this->idprof3		= trim($this->idprof3);
        $this->idprof4		= trim($this->idprof4);
        $this->idprof5		= (! empty($this->idprof5)?trim($this->idprof5):'');
        $this->idprof6		= (! empty($this->idprof6)?trim($this->idprof6):'');
        $this->prefix_comm	= trim($this->prefix_comm);

        $this->tva_assuj	= trim($this->tva_assuj);
        $this->tva_intra	= dol_sanitizeFileName($this->tva_intra,'');

        // Local taxes
        $this->localtax1_assuj=trim($this->localtax1_assuj);
        $this->localtax2_assuj=trim($this->localtax2_assuj);

        $this->capital=price2num(trim($this->capital),'MT');
        if (empty($this->capital)) $this->capital = 0;

        $this->effectif_id=trim($this->effectif_id);
        $this->forme_juridique_code=trim($this->forme_juridique_code);

        //barcode
        $this->barcode=trim($this->barcode);

        // For automatic creation
        if ($this->code_client == -1) $this->get_codeclient($this->prefix_comm,0);
        if ($this->code_fournisseur == -1) $this->get_codefournisseur($this->prefix_comm,1);

        $this->code_compta=trim($this->code_compta);
        $this->code_compta_fournisseur=trim($this->code_compta_fournisseur);

        // Check parameters
        if (! empty($conf->global->SOCIETE_MAIL_REQUIRED) && ! isValidEMail($this->email))
        {
            $langs->load("errors");
            $this->error = $langs->trans("ErrorBadEMail",$this->email);
            return -1;
        }

        // Check name is required and codes are ok or unique.
        // If error, this->errors[] is filled
        $result = $this->verify();

        if ($result >= 0)
        {
            dol_syslog(get_class($this)."::Update verify ok");

            $sql = "UPDATE ".MAIN_DB_PREFIX."societe";
            $sql.= " SET nom = '" . addslashes($this->name) ."'"; // Champ obligatoire
            $sql.= ",datea = '".$this->db->idate(mktime())."'";
            $sql.= ",address = '" . addslashes($this->address) ."'";

            $sql.= ",zip = ".($this->zip?"'".$this->zip."'":"null");
            $sql.= ",town = ".($this->town?"'".addslashes($this->town)."'":"null");

            $sql .= ",fk_departement = '" . ($this->state_id?$this->state_id:'0') ."'";
            $sql .= ",fk_pays = '" . ($this->country_id?$this->country_id:'0') ."'";

            $sql .= ",phone = ".($this->phone?"'".addslashes($this->phone)."'":"null");
            $sql .= ",fax = ".($this->fax?"'".addslashes($this->fax)."'":"null");
            $sql .= ",email = ".($this->email?"'".addslashes($this->email)."'":"null");
            $sql .= ",url = ".($this->url?"'".addslashes($this->url)."'":"null");

            $sql .= ",siren   = '". addslashes($this->idprof1) ."'";
            $sql .= ",siret   = '". addslashes($this->idprof2) ."'";
            $sql .= ",ape     = '". addslashes($this->idprof3) ."'";
            $sql .= ",idprof4 = '". addslashes($this->idprof4) ."'";

            $sql .= ",tva_assuj = ".($this->tva_assuj!=''?"'".$this->tva_assuj."'":"null");
            $sql .= ",tva_intra = '" . addslashes($this->tva_intra) ."'";

            // Local taxes
            $sql .= ",localtax1_assuj = ".($this->localtax1_assuj!=''?"'".$this->localtax1_assuj."'":"null");
            $sql .= ",localtax2_assuj = ".($this->localtax2_assuj!=''?"'".$this->localtax2_assuj."'":"null");

            $sql .= ",capital = ".$this->capital;

            $sql .= ",prefix_comm = ".($this->prefix_comm?"'".addslashes($this->prefix_comm)."'":"null");

            $sql .= ",fk_effectif = ".($this->effectif_id?"'".$this->effectif_id."'":"null");

            $sql .= ",fk_typent = ".($this->typent_id?"'".$this->typent_id."'":"0");

            $sql .= ",fk_forme_juridique = ".($this->forme_juridique_code?"'".$this->forme_juridique_code."'":"null");

            $sql .= ",client = " . ($this->client?$this->client:0);
            $sql .= ",fournisseur = " . ($this->fournisseur?$this->fournisseur:0);
            $sql .= ",barcode = ".($this->barcode?"'".$this->barcode."'":"null");
            $sql .= ",default_lang = ".($this->default_lang?"'".$this->default_lang."'":"null");


            if ($allowmodcodeclient)
            {
                //$this->check_codeclient();

                $sql .= ", code_client = ".($this->code_client?"'".addslashes($this->code_client)."'":"null");

                // Attention get_codecompta peut modifier le code suivant le module utilise
                if (empty($this->code_compta)) $this->get_codecompta('customer');

                $sql .= ", code_compta = ".($this->code_compta?"'".addslashes($this->code_compta)."'":"null");
            }

            if ($allowmodcodefournisseur)
            {
                //$this->check_codefournisseur();

                $sql .= ", code_fournisseur = ".($this->code_fournisseur?"'".addslashes($this->code_fournisseur)."'":"null");

                // Attention get_codecompta peut modifier le code suivant le module utilise
                if (empty($this->code_compta_fournisseur)) $this->get_codecompta('supplier');

                $sql .= ", code_compta_fournisseur = ".($this->code_compta_fournisseur?"'".addslashes($this->code_compta_fournisseur)."'":"null");
            }
            $sql .= ", fk_user_modif = ".($user->id > 0 ? "'".$user->id."'":"null");
            $sql .= " WHERE rowid = '" . $id ."'";


            dol_syslog(get_class($this)."::update sql=".$sql);
            $resql=$this->db->query($sql);
            if ($resql)
            {
                // Si le fournisseur est classe on l'ajoute
                $this->AddFournisseurInCategory($this->fournisseur_categorie);

                if ($call_trigger)
                {
                    // Appel des triggers
                    include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
                    $interface=new Interfaces($this->db);
                    $result=$interface->run_triggers('COMPANY_MODIFY',$this,$user,$langs,$conf);
                    if ($result < 0) { $error++; $this->errors=$interface->errors; }
                    // Fin appel triggers
                }

                $result = 1;
            }
            else
            {
                if ($this->db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS')
                {
                    // Doublon
                    $this->error = $langs->trans("ErrorDuplicateField");
                    $result =  -1;
                }
                else
                {

                    $this->error = $langs->trans("Error sql=".$sql);
                    dol_syslog(get_class($this)."::Update echec sql=".$sql);
                    $result =  -2;
                }
            }
        }

        return $result;

    }

    /**
     *    Load a third party from database into memory
     *
     *    @param      int		$rowid			Id of third party to load
     *    @param      string	$ref			Reference of third party, name (Warning, this can return several records)
     *    @param      string	$ref_ext       	External reference of third party (Warning, this information is a free field not provided by Dolibarr)
     *    @param      int		$idprof1		Prof id 1 of third party (Warning, this can return several records)
     *    @param      int		$idprof2		Prof id 2 of third party (Warning, this can return several records)
     *    @param      int		$idprof3		Prof id 3 of third party (Warning, this can return several records)
     *    @param      int		$idprof4		Prof id 4 of third party (Warning, this can return several records)
     *    @return     $int						>0 if OK, <0 if KO or if two records found for same ref or idprof.
     */
    function fetch($rowid, $ref='', $ref_ext='', $idprof1='',$idprof2='',$idprof3='',$idprof4='')
    {
        global $langs;
        global $conf;

        if (empty($rowid) && empty($ref) && empty($ref_ext)) return -1;

        $sql = 'SELECT s.rowid, s.nom as name, s.entity, s.ref_ext, s.address, s.datec as dc, s.prefix_comm';
        $sql .= ', s.status';
        $sql .= ', s.price_level';
        $sql .= ', s.tms as date_update';
        $sql .= ', s.phone, s.fax, s.email, s.url, s.zip as zip, s.town as town, s.note_public, s.note_private, s.client, s.fournisseur';
        $sql .= ', s.siren, s.siret, s.ape, s.idprof4';
        $sql .= ', s.capital, s.tva_intra';
        $sql .= ', s.fk_typent as typent_id';
        $sql .= ', s.fk_effectif as effectif_id';
        $sql .= ', s.fk_forme_juridique as forme_juridique_code';
        $sql .= ', s.code_client, s.code_fournisseur, s.code_compta, s.code_compta_fournisseur, s.parent, s.barcode';
        $sql .= ', s.fk_departement, s.fk_pays, s.fk_stcomm, s.remise_client, s.mode_reglement, s.cond_reglement, s.tva_assuj';
        $sql .= ', s.mode_reglement_supplier, s.cond_reglement_supplier, s.localtax1_assuj, s.localtax1_value, s.localtax2_assuj, s.localtax2_value, s.fk_prospectlevel, s.default_lang, s.logo';
        $sql .= ', s.outstanding_limit, s.import_key, s.canvas';
        $sql .= ', fj.libelle as forme_juridique';
        $sql .= ', e.libelle as effectif';
        $sql .= ', p.code as country_code, p.label as country';
        $sql .= ', d.code_departement as state_code, d.nom as state';
        $sql .= ', st.libelle as stcomm';
        $sql .= ', te.code as typent_code';
       // $sql .= ', sa.note_antemed, sa.note_antechirgen, sa.note_antechirortho, sa.note_anterhum, sa.note_other';
        //$sql .= ', sa.note_traitclass, sa.note_traitallergie, sa.note_traitintol, sa.note_traitspec';
        //$sql .= ', sa.alert_antemed, sa.alert_antechirgen, sa.alert_antechirortho, sa.alert_anterhum, sa.alert_other';
        //$sql .= ', sa.alert_traitclass, sa.alert_traitallergie, sa.alert_traitintol, sa.alert_traitspec';
        $sql .= ', sa.alert_note';
        $sql .= ' FROM '.MAIN_DB_PREFIX.'societe as s';
        //$sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'cabinetmed_patient as sa ON sa.rowid = s.rowid';
        $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_effectif as e ON s.fk_effectif = e.id';
        $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_country as p ON s.fk_pays = p.rowid';
        $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_stcomm as st ON s.fk_stcomm = st.id';
        $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_forme_juridique as fj ON s.fk_forme_juridique = fj.code';
        $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_departements as d ON s.fk_departement = d.rowid';
        $sql .= ' LEFT JOIN '.MAIN_DB_PREFIX.'c_typent as te ON s.fk_typent = te.id';
        if ($rowid) $sql .= ' WHERE s.rowid = '.$rowid;
        if ($ref)   $sql .= " WHERE s.nom = '".$this->db->escape($ref)."' AND s.entity = ".$conf->entity;
        if ($ref_ext) $sql .= " WHERE s.ref_ext = '".$this->db->escape($ref_ext)."' AND s.entity = ".$conf->entity;
        if ($idprof1) $sql .= " WHERE s.siren = '".$this->db->escape($siren)."' AND s.entity = ".$conf->entity;
        if ($idprof2) $sql .= " WHERE s.siret = '".$this->db->escape($siret)."' AND s.entity = ".$conf->entity;
        if ($idprof3) $sql .= " WHERE s.ape = '".$this->db->escape($ape)."' AND s.entity = ".$conf->entity;
        if ($idprof4) $sql .= " WHERE s.idprof4 = '".$this->db->escape($idprof4)."' AND s.entity = ".$conf->entity;
        //print $sql;

        $resql=$this->db->query($sql);
        dol_syslog(get_class($this)."::fetch ".$sql);
        if ($resql)
        {
            $num=$this->db->num_rows($resql);
            if ($num > 1)
            {
                $this->error='several records found for ref='.$ref;
                dol_syslog($this->error, LOG_ERR);
                $result = -1;
            }
            if ($num)
            {
                $obj = $this->db->fetch_object($resql);

                $this->id           = $obj->rowid;
                $this->entity       = $obj->entity;
                $this->canvas		= $obj->canvas;

                $this->ref          = $obj->rowid;
                $this->nom 			= $obj->name; // deprecated
                $this->name 		= $obj->name;
                $this->ref_ext      = $obj->ref_ext;

                $this->datec = $this->db->jdate($obj->datec);
                $this->date_update = $this->db->jdate($obj->date_update);

                $this->address 		= $obj->address;
                $this->zip 			= $obj->zip;
                $this->town 		= $obj->town;

                $this->country_id   = $obj->fk_pays;
                $this->country_code = $obj->fk_pays?$obj->country_code:'';
                $this->country 		= $obj->fk_pays?($langs->trans('Country'.$obj->country_code)!='Country'.$obj->country_code?$langs->trans('Country'.$obj->country_code):$obj->country):'';

                $this->state_id     = $obj->fk_departement;
                $this->state_code   = $obj->fk_departement?$obj->state_code:'';
                $this->state        = $obj->fk_departement?$obj->state:'';

                $transcode=$langs->trans('StatusProspect'.$obj->fk_stcomm);
                $libelle=($transcode!='StatusProspect'.$obj->fk_stcomm?$transcode:$obj->stcomm);
                $this->stcomm_id = $obj->fk_stcomm;     // id statut commercial
                $this->statut_commercial = $libelle;    // libelle statut commercial

                $this->email = $obj->email;
                $this->url = $obj->url;
                $this->phone = $obj->phone;
                $this->fax = $obj->fax;

                $this->parent    = $obj->parent;

                $this->idprof1		= $obj->idprof1;
                $this->idprof2		= $obj->idprof2;
                $this->idprof3		= $obj->idprof3;
                $this->idprof4		= $obj->idprof4;

                $this->capital   = $obj->capital;

                $this->code_client = $obj->code_client;
                $this->code_fournisseur = $obj->code_fournisseur;

                $this->code_compta = $obj->code_compta;
                $this->code_compta_fournisseur = $obj->code_compta_fournisseur;

                $this->barcode = $obj->barcode;

                $this->tva_assuj      = $obj->tva_assuj;
                $this->tva_intra      = $obj->tva_intra;
                $this->status = $obj->status;
                
                // Local Taxes
                $this->localtax1_assuj      = $obj->localtax1_assuj;
                $this->localtax2_assuj      = $obj->localtax2_assuj;


                $this->typent_id      = $obj->typent_id;
                $this->typent_code    = $obj->typent_code;

                $this->effectif_id    = $obj->effectif_id;
                $this->effectif       = $obj->effectif_id?$obj->effectif:'';

                $this->forme_juridique_code= $obj->forme_juridique_code;
                $this->forme_juridique     = $obj->forme_juridique_code?$obj->forme_juridique:'';

                $this->fk_prospectlevel = $obj->fk_prospectlevel;

                $this->prefix_comm = $obj->prefix_comm;

                $this->remise_percent		= $obj->remise_client;
                $this->mode_reglement_id 	= $obj->mode_reglement;
                $this->cond_reglement_id 	= $obj->cond_reglement;

                $this->client      = $obj->client;
                $this->fournisseur = $obj->fournisseur;

                $this->note_private = $obj->note_private;
                $this->default_lang = $obj->default_lang;

                // multiprix
                $this->price_level = $obj->price_level;

                $this->import_key = $obj->import_key;

                //$this->note_antemed = $obj->note_antemed;
                //$this->note_antechirgen = $obj->note_antechirgen;
                //$this->note_antechirortho = $obj->note_antechirortho;
                //$this->note_anterhum = $obj->note_anterhum;
                //$this->note_other = $obj-> note_other;

                //$this->note_traitclass = $obj->note_traitclass;
                //$this->note_traitallergie = $obj->note_traitallergie;
                //$this->note_traitintol = $obj->note_traitintol;
                //$this->note_traitspec = $obj->note_traitspec;

                //$this->alert_antemed = $obj->alert_antemed;
                //$this->alert_antechirgen = $obj->alert_antechirgen;
                //$this->alert_antechirortho = $obj->alert_antechirortho;
                //$this->alert_anterhum = $obj->alert_anterhum;
                //$this->alert_other = $obj->alert_other;
                //$this->alert_traitclass = $obj->alert_traitclass;
                //$this->alert_traitallergie = $obj->alert_traitallergie;
                //$this->alert_traitintol = $obj->alert_traitintol;
                //$this->alert_traitspec = $obj->alert_traitspec;
                //$this->alert_note = $obj->alert_note;

                $this->fetch_optionals();
                
                $result = 1;
            }
            else
            {
                $result = 0;
            }

            $this->db->free($resql);
        }
        else
        {
            dol_syslog('Error '.$this->db->lasterror(), LOG_ERR);
            $this->error=$this->db->lasterror();
            $result = -3;
        }

        // Use first price level if level not defined for third party
        if ($conf->global->PRODUIT_MULTIPRICES && empty($this->price_level)) $this->price_level=1;

        return $result;
    }


    /**
     *  Initialise an example of company with random values
     *  Used to build previews or test instances
     *
     *	@return	void
     */
    function initAsSpecimen()
    {
        global $user,$langs,$conf,$mysoc;

        $now=dol_now();

        // Initialize parameters
        $this->id=0;
        $this->name = 'RECEIPT SPECIMEN '.dol_print_date($now,'dayhourlog');
        $this->specimen=1;
        $this->zip='99999';
        $this->town='MyTown';
        $this->country_id=1;
        $this->country_code='FR';

        $this->code_client='CC-'.dol_print_date($now,'dayhourlog');
        $this->code_fournisseur='SC-'.dol_print_date($now,'dayhourlog');
        $this->capital=10000;
        $this->client=1;
        $this->prospect=1;
        $this->fournisseur=1;
        $this->tva_assuj=1;
        $this->tva_intra='EU1234567';
        $this->note_public='This is a comment (public)';
        $this->note_private='This is a comment (private)';

        $this->idprof1='idprof1';
        $this->idprof2='idprof2';
        $this->idprof3='idprof3';
        $this->idprof4='idprof4';
    }

}

