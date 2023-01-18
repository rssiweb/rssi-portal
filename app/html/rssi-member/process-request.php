<?php
if(isset($_POST["typeofleave"])){
$typeofleave = $_POST["typeofleave"];
$typeofleaveArr = array("Sick Leave" => array("Abdominal/Pelvic pain",
"Anemia",
"Appendicitis / Pancreatitis",
"Asthma / bronchitis / pneumonia",
"Burns",
"Cancer -Carcinoma/ Malignant neoplasm",
"Cardiac related ailments or Heart Disease",
"Chest Pain",
"Convulsions/ Epilepsy",
"Dental Related Ailments - Tooth Ache / Impacted Tooth",
"Emotional Well Being",
"Digestive System Disorders/Indigestion/Food Poisoning/Diarrhea/Dysentry/Gastritis & Enteritis",
"Excessive vomiting in pregnancy/Pregnancy induced hypertension",
"Eye Related Ailments -Low Vision/Blindness/Eye Infections",
"Fever/Cough/Cold",
"Fracture/Injury/Dislocation/Sprain/Strain of joints/Ligaments of knee/Internal derangement/Other Orthopedic related ailments",
"Gynecological Ailments/Disorders -Endometriosis/Fibroids",
"Haemorrhoids (Piles)/Fissure/Fistula",
"Headache/Nausea/Vomiting",
"Hernia - Inguinal / Umbilical / Ventral",
"Hepatitis",
"Liver Related Ailments",
"Maternity-Normal Delivery/Caesarean Section/Abortion",
"Nervous Disorders",
"Quarantine Leave",
"Respiratory Related Ailments-Sinusitis/Tonsillitis,/Chronic rhinitis/Nasopharyngitis and pharyngitis/Congenital malformations of nose bronchitis",
"Skin Related Ailments-Abscess/Swelling",
"Spondilitis/ Intervertebral Disc Disorders / Spondylosis",
"Urinary Tract Infections/Disorders",
"Varicose veins of other sites",),
                    "Casual Leave" => array("Other", "Timesheet leave"),
                    "Leave Without Pay" => array(""));
                    
if($typeofleave !== 'Select'){
        echo "<span class='input-help'>";
        if($typeofleave !== 'Leave Without Pay'){
        echo "<select name='creason' id='creason' class='form-control' required>";
        } else {
        echo "<select name='creason' id='creason' class='form-control'>";
        }
        echo "<option disabled selected hidden value=''>Select</option>";
        foreach($typeofleaveArr[$typeofleave] as $value){
            echo "<option>". $value . "</option>";
        }
        echo "</select>
        <small id='passwordHelpBlock' class='form-text text-muted'>Leave Category<span style='color:red'>*</span></small>
        </span>";
    } 
}
