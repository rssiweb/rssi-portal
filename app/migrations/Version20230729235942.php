<?php

declare(strict_types=1);

namespace Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230729235942 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('CREATE TABLE candidatepool (application_number TEXT NOT NULL, applicant_f_name TEXT DEFAULT NULL, applicant_l_name TEXT DEFAULT NULL, national_identifier TEXT DEFAULT NULL, national_identifier_number TEXT DEFAULT NULL, email TEXT DEFAULT NULL, contact TEXT DEFAULT NULL, base_branch TEXT DEFAULT NULL, association_type TEXT DEFAULT NULL, supporting_document TEXT DEFAULT NULL, cv TEXT DEFAULT NULL, appliedon DATE DEFAULT NULL, PRIMARY KEY(application_number))');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('CREATE TABLE donation_userdata (tel VARCHAR(10) NOT NULL, fullname VARCHAR(100) DEFAULT NULL, email VARCHAR(100) DEFAULT NULL, documenttype VARCHAR(50) DEFAULT NULL, nationalid VARCHAR(50) DEFAULT NULL, postaladdress VARCHAR(200) DEFAULT NULL, PRIMARY KEY(tel))');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('CREATE TABLE test (id SERIAL NOT NULL, date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, sname TEXT DEFAULT NULL, sid TEXT DEFAULT NULL, amount NUMERIC(10, 0) DEFAULT NULL, orderid TEXT DEFAULT NULL, orderstatus TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('CREATE TABLE new_result (id SERIAL NOT NULL, name TEXT DEFAULT NULL, studentid TEXT DEFAULT NULL, category TEXT DEFAULT NULL, class TEXT DEFAULT NULL, dob TEXT DEFAULT NULL, hnd TEXT DEFAULT NULL, eng TEXT DEFAULT NULL, mth TEXT DEFAULT NULL, sce TEXT DEFAULT NULL, gka TEXT DEFAULT NULL, ssc TEXT DEFAULT NULL, phy TEXT DEFAULT NULL, chm TEXT DEFAULT NULL, bio TEXT DEFAULT NULL, com TEXT DEFAULT NULL, hd TEXT DEFAULT NULL, acc TEXT DEFAULT NULL, pt TEXT DEFAULT NULL, total TEXT DEFAULT NULL, mm TEXT DEFAULT NULL, op TEXT DEFAULT NULL, grade TEXT DEFAULT NULL, result TEXT DEFAULT NULL, "position" TEXT DEFAULT NULL, attd TEXT DEFAULT NULL, examname TEXT DEFAULT NULL, fullmarks TEXT DEFAULT NULL, month TEXT DEFAULT NULL, language1 TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('CREATE TABLE donation_paymentdata (donationid TEXT NOT NULL, tel VARCHAR(10) DEFAULT NULL, currency VARCHAR(50) DEFAULT NULL, amount NUMERIC(10, 2) DEFAULT NULL, transactionid VARCHAR(50) DEFAULT NULL, message TEXT DEFAULT NULL, "timestamp" TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(donationid))');
        $this->addSql('CREATE INDEX IDX_AF5ED5B0F037AB0F ON donation_paymentdata (tel)');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('CREATE TABLE leaveadjustment (leaveadjustmentid TEXT NOT NULL, adj_applicantid TEXT DEFAULT NULL, adj_day NUMERIC(10, 0) DEFAULT NULL, adj_academicyear TEXT DEFAULT NULL, adj_fromdate DATE DEFAULT NULL, adj_todate DATE DEFAULT NULL, adj_regdate TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, adj_reason TEXT DEFAULT NULL, adj_leavetype TEXT DEFAULT NULL, adj_appliedby TEXT DEFAULT NULL, adj_appliedby_name TEXT DEFAULT NULL, PRIMARY KEY(leaveadjustmentid))');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('CREATE TABLE appraisee_response (id SERIAL NOT NULL, goalsheetid TEXT DEFAULT NULL, appraisee_associatenumber TEXT DEFAULT NULL, manager_associatenumber TEXT DEFAULT NULL, reviewer_associatenumber TEXT DEFAULT NULL, role TEXT DEFAULT NULL, appraisaltype TEXT DEFAULT NULL, appraisalyear TEXT DEFAULT NULL, parameter_1 TEXT DEFAULT NULL, expectation_1 TEXT DEFAULT NULL, max_rating_1 INT DEFAULT NULL, parameter_2 TEXT DEFAULT NULL, expectation_2 TEXT DEFAULT NULL, max_rating_2 INT DEFAULT NULL, parameter_3 TEXT DEFAULT NULL, expectation_3 TEXT DEFAULT NULL, max_rating_3 INT DEFAULT NULL, parameter_4 TEXT DEFAULT NULL, expectation_4 TEXT DEFAULT NULL, max_rating_4 INT DEFAULT NULL, parameter_5 TEXT DEFAULT NULL, expectation_5 TEXT DEFAULT NULL, max_rating_5 INT DEFAULT NULL, parameter_6 TEXT DEFAULT NULL, expectation_6 TEXT DEFAULT NULL, max_rating_6 INT DEFAULT NULL, parameter_7 TEXT DEFAULT NULL, expectation_7 TEXT DEFAULT NULL, max_rating_7 INT DEFAULT NULL, parameter_8 TEXT DEFAULT NULL, expectation_8 TEXT DEFAULT NULL, max_rating_8 INT DEFAULT NULL, parameter_9 TEXT DEFAULT NULL, expectation_9 TEXT DEFAULT NULL, max_rating_9 INT DEFAULT NULL, parameter_10 TEXT DEFAULT NULL, expectation_10 TEXT DEFAULT NULL, max_rating_10 INT DEFAULT NULL, parameter_11 TEXT DEFAULT NULL, expectation_11 TEXT DEFAULT NULL, max_rating_11 INT DEFAULT NULL, parameter_12 TEXT DEFAULT NULL, expectation_12 TEXT DEFAULT NULL, max_rating_12 INT DEFAULT NULL, parameter_13 TEXT DEFAULT NULL, expectation_13 TEXT DEFAULT NULL, max_rating_13 INT DEFAULT NULL, parameter_14 TEXT DEFAULT NULL, expectation_14 TEXT DEFAULT NULL, max_rating_14 INT DEFAULT NULL, parameter_15 TEXT DEFAULT NULL, expectation_15 TEXT DEFAULT NULL, max_rating_15 INT DEFAULT NULL, parameter_16 TEXT DEFAULT NULL, expectation_16 TEXT DEFAULT NULL, max_rating_16 INT DEFAULT NULL, parameter_17 TEXT DEFAULT NULL, expectation_17 TEXT DEFAULT NULL, max_rating_17 INT DEFAULT NULL, parameter_18 TEXT DEFAULT NULL, expectation_18 TEXT DEFAULT NULL, max_rating_18 INT DEFAULT NULL, parameter_19 TEXT DEFAULT NULL, expectation_19 TEXT DEFAULT NULL, max_rating_19 INT DEFAULT NULL, parameter_20 TEXT DEFAULT NULL, expectation_20 TEXT DEFAULT NULL, max_rating_20 INT DEFAULT NULL, appraisee_response_2 TEXT DEFAULT NULL, appraisee_response_3 TEXT DEFAULT NULL, appraisee_response_4 TEXT DEFAULT NULL, appraisee_response_5 TEXT DEFAULT NULL, appraisee_response_6 TEXT DEFAULT NULL, appraisee_response_7 TEXT DEFAULT NULL, appraisee_response_8 TEXT DEFAULT NULL, appraisee_response_9 TEXT DEFAULT NULL, appraisee_response_10 TEXT DEFAULT NULL, appraisee_response_11 TEXT DEFAULT NULL, appraisee_response_12 TEXT DEFAULT NULL, appraisee_response_13 TEXT DEFAULT NULL, appraisee_response_14 TEXT DEFAULT NULL, appraisee_response_15 TEXT DEFAULT NULL, appraisee_response_16 TEXT DEFAULT NULL, appraisee_response_17 TEXT DEFAULT NULL, appraisee_response_18 TEXT DEFAULT NULL, appraisee_response_19 TEXT DEFAULT NULL, appraisee_response_20 TEXT DEFAULT NULL, rating_obtained_1 INT DEFAULT NULL, rating_obtained_2 INT DEFAULT NULL, rating_obtained_3 INT DEFAULT NULL, rating_obtained_4 INT DEFAULT NULL, rating_obtained_5 INT DEFAULT NULL, rating_obtained_6 INT DEFAULT NULL, rating_obtained_7 INT DEFAULT NULL, rating_obtained_8 INT DEFAULT NULL, rating_obtained_9 INT DEFAULT NULL, rating_obtained_10 INT DEFAULT NULL, rating_obtained_11 INT DEFAULT NULL, rating_obtained_12 INT DEFAULT NULL, rating_obtained_13 INT DEFAULT NULL, rating_obtained_14 INT DEFAULT NULL, rating_obtained_15 INT DEFAULT NULL, rating_obtained_16 INT DEFAULT NULL, rating_obtained_17 INT DEFAULT NULL, rating_obtained_18 INT DEFAULT NULL, rating_obtained_19 INT DEFAULT NULL, rating_obtained_20 INT DEFAULT NULL, manager_remarks_1 TEXT DEFAULT NULL, manager_remarks_2 TEXT DEFAULT NULL, manager_remarks_3 TEXT DEFAULT NULL, manager_remarks_4 TEXT DEFAULT NULL, manager_remarks_5 TEXT DEFAULT NULL, manager_remarks_6 TEXT DEFAULT NULL, manager_remarks_7 TEXT DEFAULT NULL, manager_remarks_8 TEXT DEFAULT NULL, manager_remarks_9 TEXT DEFAULT NULL, manager_remarks_10 TEXT DEFAULT NULL, manager_remarks_11 TEXT DEFAULT NULL, manager_remarks_12 TEXT DEFAULT NULL, manager_remarks_13 TEXT DEFAULT NULL, manager_remarks_14 TEXT DEFAULT NULL, manager_remarks_15 TEXT DEFAULT NULL, manager_remarks_16 TEXT DEFAULT NULL, manager_remarks_17 TEXT DEFAULT NULL, manager_remarks_18 TEXT DEFAULT NULL, manager_remarks_19 TEXT DEFAULT NULL, manager_remarks_20 TEXT DEFAULT NULL, appraisee_response_complete TEXT DEFAULT NULL, manager_evaluation_complete TEXT DEFAULT NULL, reviewer_response_complete TEXT DEFAULT NULL, appraisee_response_1 TEXT DEFAULT NULL, reviewer_remarks TEXT DEFAULT NULL, goalsheet_created_by TEXT DEFAULT NULL, goalsheet_created_on TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, goalsheet_submitted_by TEXT DEFAULT NULL, goalsheet_submitted_on TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, goalsheet_evaluated_by TEXT DEFAULT NULL, goalsheet_evaluated_on TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, goalsheet_reviewed_by TEXT DEFAULT NULL, goalsheet_reviewed_on TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, ipf NUMERIC(10, 0) DEFAULT NULL, ipf_response TEXT DEFAULT NULL, ipf_response_by TEXT DEFAULT NULL, ipf_response_on TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, ipf_process_closed_on TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, ipf_process_closed_by TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('CREATE TABLE userlog_member (id SERIAL NOT NULL, username TEXT DEFAULT NULL, ipaddress TEXT DEFAULT NULL, logintime TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('CREATE TABLE asset (submissionid TEXT NOT NULL, userid TEXT DEFAULT NULL, usertype TEXT DEFAULT NULL, assetdetails TEXT DEFAULT NULL, agreement TEXT DEFAULT NULL, issuedon TEXT DEFAULT NULL, returnedon TEXT DEFAULT NULL, receivedon TEXT DEFAULT NULL, status TEXT DEFAULT NULL, comment TEXT DEFAULT NULL, category TEXT DEFAULT NULL, agreementname TEXT DEFAULT NULL, "timestamp" TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(submissionid))');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('CREATE TABLE visitor (slno INT NOT NULL, "timestamp" TEXT DEFAULT NULL, visitorname TEXT DEFAULT NULL, purposeofvisit TEXT DEFAULT NULL, visitdatefrom TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, visitdateto TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, aadharcard TEXT DEFAULT NULL, photo TEXT DEFAULT NULL, visitorid TEXT DEFAULT NULL, status TEXT DEFAULT NULL, authority TEXT DEFAULT NULL, visited TEXT DEFAULT NULL, existingid TEXT DEFAULT NULL, contact TEXT DEFAULT NULL, email TEXT DEFAULT NULL, branch TEXT DEFAULT NULL, visittime TEXT DEFAULT NULL, raw_visitorname TEXT DEFAULT NULL, raw_aadharcard TEXT DEFAULT NULL, raw_photo TEXT DEFAULT NULL, raw_contact TEXT DEFAULT NULL, PRIMARY KEY(slno))');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('CREATE TABLE result (id INT NOT NULL, name TEXT DEFAULT NULL, studentid TEXT DEFAULT NULL, category TEXT DEFAULT NULL, class TEXT DEFAULT NULL, hnd_o TEXT DEFAULT NULL, hnd TEXT DEFAULT NULL, eng_o TEXT DEFAULT NULL, eng TEXT DEFAULT NULL, mth_o TEXT DEFAULT NULL, mth TEXT DEFAULT NULL, sce_o TEXT DEFAULT NULL, sce TEXT DEFAULT NULL, gka_o TEXT DEFAULT NULL, gka TEXT DEFAULT NULL, ssc_o TEXT DEFAULT NULL, ssc TEXT DEFAULT NULL, phy_o TEXT DEFAULT NULL, phy TEXT DEFAULT NULL, chm_o TEXT DEFAULT NULL, chm TEXT DEFAULT NULL, bio_o TEXT DEFAULT NULL, bio TEXT DEFAULT NULL, com_o TEXT DEFAULT NULL, com TEXT DEFAULT NULL, hd_o TEXT DEFAULT NULL, hd TEXT DEFAULT NULL, acc_o TEXT DEFAULT NULL, acc TEXT DEFAULT NULL, pt_o TEXT DEFAULT NULL, pt TEXT DEFAULT NULL, total TEXT DEFAULT NULL, mm TEXT DEFAULT NULL, op TEXT DEFAULT NULL, grade TEXT DEFAULT NULL, result TEXT DEFAULT NULL, "position" TEXT DEFAULT NULL, fullmarks_o TEXT DEFAULT NULL, fullmarks TEXT DEFAULT NULL, examname TEXT DEFAULT NULL, language1 TEXT DEFAULT NULL, attd TEXT DEFAULT NULL, month TEXT DEFAULT NULL, academicyear TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('CREATE TABLE wbt_status (id SERIAL NOT NULL, wassociatenumber TEXT DEFAULT NULL, courseid TEXT DEFAULT NULL, "timestamp" TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, f_score NUMERIC(10, 0) DEFAULT NULL, email TEXT DEFAULT NULL, noticebody TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('CREATE TABLE rssimyprofile_student (student_id TEXT NOT NULL, category TEXT DEFAULT NULL, roll_number TEXT DEFAULT NULL, studentname TEXT DEFAULT NULL, gender TEXT DEFAULT NULL, age TEXT DEFAULT NULL, class TEXT DEFAULT NULL, contact TEXT DEFAULT NULL, guardiansname TEXT DEFAULT NULL, relationwithstudent TEXT DEFAULT NULL, studentaadhar TEXT DEFAULT NULL, guardianaadhar TEXT DEFAULT NULL, dateofbirth TEXT DEFAULT NULL, postaladdress TEXT DEFAULT NULL, nameofthesubjects TEXT DEFAULT NULL, preferredbranch TEXT DEFAULT NULL, nameoftheschool TEXT DEFAULT NULL, nameoftheboard TEXT DEFAULT NULL, stateofdomicile TEXT DEFAULT NULL, emailaddress TEXT DEFAULT NULL, schooladmissionrequired TEXT DEFAULT NULL, status TEXT DEFAULT NULL, remarks TEXT DEFAULT NULL, nameofvendorfoundation TEXT DEFAULT NULL, photourl TEXT DEFAULT NULL, familymonthlyincome TEXT DEFAULT NULL, totalnumberoffamilymembers TEXT DEFAULT NULL, medium TEXT DEFAULT NULL, mydocument TEXT DEFAULT NULL, extracolumn TEXT DEFAULT NULL, colors TEXT DEFAULT NULL, classurl TEXT DEFAULT NULL, badge TEXT DEFAULT NULL, filterstatus TEXT DEFAULT NULL, allocationdate TEXT DEFAULT NULL, maxclass TEXT DEFAULT NULL, attd TEXT DEFAULT NULL, cltaken TEXT DEFAULT NULL, sltaken TEXT DEFAULT NULL, othtaken TEXT DEFAULT NULL, doa TEXT DEFAULT NULL, feesflag TEXT DEFAULT NULL, module TEXT DEFAULT NULL, scode TEXT DEFAULT NULL, exitinterview TEXT DEFAULT NULL, sipf TEXT DEFAULT NULL, password TEXT DEFAULT NULL, password_updated_by TEXT DEFAULT NULL, password_updated_on TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, upload_aadhar_card TEXT DEFAULT NULL, special_service TEXT DEFAULT NULL, feecycle TEXT DEFAULT NULL, default_pass_updated_by TEXT DEFAULT NULL, default_pass_updated_on TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, effectivefrom TEXT DEFAULT NULL, PRIMARY KEY(student_id))');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('CREATE TABLE claim (reimbid TEXT NOT NULL, registrationid TEXT DEFAULT NULL, selectclaimheadfromthelistbelow TEXT DEFAULT NULL, billno TEXT DEFAULT NULL, currency TEXT DEFAULT NULL, totalbillamount NUMERIC(10, 0) DEFAULT NULL, uploadeddocuments TEXT DEFAULT NULL, ack TEXT DEFAULT NULL, year TEXT DEFAULT NULL, claimstatus TEXT DEFAULT NULL, approvedamount NUMERIC(10, 0) DEFAULT NULL, transactionid TEXT DEFAULT NULL, mediremarks TEXT DEFAULT NULL, claimheaddetails TEXT DEFAULT NULL, "timestamp" TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, transfereddate DATE DEFAULT NULL, closedon DATE DEFAULT NULL, reviewer_id TEXT DEFAULT NULL, reviewer_name TEXT DEFAULT NULL, updatedon TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(reimbid))');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('CREATE TABLE gems (redeem_id TEXT NOT NULL, user_id TEXT DEFAULT NULL, user_name TEXT DEFAULT NULL, redeem_gems_point NUMERIC(10, 0) DEFAULT NULL, redeem_type TEXT DEFAULT NULL, reviewer_status TEXT DEFAULT NULL, requested_on TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, reviewer_status_updated_on TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, reviewer_remarks TEXT DEFAULT NULL, reviewer_id TEXT DEFAULT NULL, reviewer_name TEXT DEFAULT NULL, PRIMARY KEY(redeem_id))');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('CREATE TABLE leaveallocation (leaveallocationid TEXT NOT NULL, allo_applicantid TEXT DEFAULT NULL, allo_daycount NUMERIC(10, 0) DEFAULT NULL, allo_leavetype TEXT DEFAULT NULL, allo_remarks TEXT DEFAULT NULL, allocatedbyid TEXT DEFAULT NULL, allo_date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, allocatedbyname TEXT DEFAULT NULL, allo_academicyear TEXT DEFAULT NULL, PRIMARY KEY(leaveallocationid))');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('CREATE TABLE certificate (certificate_no TEXT NOT NULL, awarded_to_id TEXT DEFAULT NULL, badge_name TEXT DEFAULT NULL, comment TEXT DEFAULT NULL, gems NUMERIC(10, 0) DEFAULT NULL, issuedon TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, issuedby TEXT DEFAULT NULL, certificate_url TEXT DEFAULT NULL, awarded_to_name TEXT DEFAULT NULL, out_email TEXT DEFAULT NULL, out_phone TEXT DEFAULT NULL, out_scode TEXT DEFAULT NULL, out_flag NUMERIC(10, 0) DEFAULT NULL, PRIMARY KEY(certificate_no))');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('CREATE TABLE gps_history (id SERIAL NOT NULL, date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, itemname TEXT DEFAULT NULL, quantity TEXT DEFAULT NULL, remarks TEXT DEFAULT NULL, collectedby TEXT DEFAULT NULL, itemtype TEXT DEFAULT NULL, itemid TEXT NOT NULL, taggedto TEXT DEFAULT NULL, asset_status TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('CREATE TABLE bookdata_book (orderid TEXT NOT NULL, bookregno TEXT DEFAULT NULL, bookname TEXT DEFAULT NULL, yourid TEXT DEFAULT NULL, yourname TEXT DEFAULT NULL, email TEXT DEFAULT NULL, originalprice TEXT DEFAULT NULL, orderdate TEXT DEFAULT NULL, issuedon TEXT DEFAULT NULL, duedate TEXT DEFAULT NULL, bookstatus TEXT DEFAULT NULL, remarks TEXT DEFAULT NULL, "timestamp" TEXT DEFAULT NULL, processclose TEXT DEFAULT NULL, PRIMARY KEY(orderid))');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('CREATE TABLE wbt (courseid TEXT NOT NULL, date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, coursename TEXT DEFAULT NULL, language TEXT DEFAULT NULL, passingmarks NUMERIC(10, 0) DEFAULT NULL, url TEXT DEFAULT NULL, issuedby TEXT DEFAULT NULL, validity TEXT DEFAULT NULL, PRIMARY KEY(courseid))');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('CREATE TABLE fees (id SERIAL NOT NULL, date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, sname TEXT DEFAULT NULL, studentid TEXT DEFAULT NULL, fees NUMERIC(10, 0) DEFAULT NULL, month INT DEFAULT NULL, collectedby TEXT DEFAULT NULL, pstatus TEXT DEFAULT NULL, ptype TEXT DEFAULT NULL, feeyear INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('CREATE TABLE policy (policyid TEXT NOT NULL, policyname TEXT DEFAULT NULL, remarks TEXT DEFAULT NULL, policydoc TEXT DEFAULT NULL, policytype TEXT DEFAULT NULL, issuedby TEXT DEFAULT NULL, issuedon TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(policyid))');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('CREATE TABLE notice (noticeid TEXT NOT NULL, date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, subject TEXT DEFAULT NULL, url TEXT DEFAULT NULL, issuedby TEXT DEFAULT NULL, category TEXT DEFAULT NULL, noticebody TEXT DEFAULT NULL, PRIMARY KEY(noticeid))');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('CREATE TABLE stock_item (item_code VARCHAR(10) NOT NULL, item_name VARCHAR(50) DEFAULT NULL, PRIMARY KEY(item_code))');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('CREATE TABLE myappraisal_myappraisal (id SERIAL NOT NULL, appraisaltype VARCHAR(512) DEFAULT NULL, associatenumber VARCHAR(512) DEFAULT NULL, fullname VARCHAR(512) DEFAULT NULL, effectivestartdate VARCHAR(512) DEFAULT NULL, effectiveenddate VARCHAR(512) DEFAULT NULL, role VARCHAR(512) DEFAULT NULL, feedback TEXT DEFAULT NULL, scopeofimprovement VARCHAR(1024) DEFAULT NULL, ipf VARCHAR(512) DEFAULT NULL, flag VARCHAR(512) DEFAULT NULL, filter TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('CREATE TABLE onboarding (onboarding_associate_id TEXT NOT NULL, serial_number SERIAL NOT NULL, onboarding_photo TEXT DEFAULT NULL, reporting_date_time TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, onboarding_gen_otp_associate TEXT DEFAULT NULL, onboarding_otp_associate TEXT DEFAULT NULL, onboarding_gen_otp_center_incharge TEXT DEFAULT NULL, onboarding_otp_center_incharge TEXT DEFAULT NULL, onboarding_submitted_by VARCHAR(255) DEFAULT NULL, onboarding_submitted_on TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, onboarding_flag TEXT DEFAULT NULL, onboard_initiated_by TEXT DEFAULT NULL, onboard_initiated_on TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, disclaimer TEXT DEFAULT NULL, ip_address VARCHAR(255) DEFAULT NULL, PRIMARY KEY(onboarding_associate_id))');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('CREATE TABLE student (id SERIAL NOT NULL, type_of_admission TEXT DEFAULT NULL, student_name TEXT DEFAULT NULL, date_of_birth DATE DEFAULT NULL, gender TEXT DEFAULT NULL, student_photo TEXT DEFAULT NULL, aadhar_available TEXT DEFAULT NULL, student_aadhar TEXT DEFAULT NULL, aadhar_card TEXT DEFAULT NULL, guardian_name TEXT DEFAULT NULL, guardian_relation TEXT DEFAULT NULL, guardian_aadhar TEXT DEFAULT NULL, state_of_domicile TEXT DEFAULT NULL, postal_address TEXT DEFAULT NULL, telephone_number TEXT DEFAULT NULL, email_address TEXT DEFAULT NULL, preferred_branch TEXT DEFAULT NULL, class TEXT DEFAULT NULL, school_admission_required TEXT DEFAULT NULL, school_name TEXT DEFAULT NULL, board_name TEXT DEFAULT NULL, medium TEXT DEFAULT NULL, family_monthly_income TEXT DEFAULT NULL, total_family_members TEXT DEFAULT NULL, payment_mode TEXT DEFAULT NULL, c_authentication_code TEXT DEFAULT NULL, transaction_id TEXT DEFAULT NULL, student_id TEXT DEFAULT NULL, subject_select TEXT DEFAULT NULL, module TEXT DEFAULT NULL, category TEXT DEFAULT NULL, photo_url TEXT DEFAULT NULL, id_card_issued TEXT DEFAULT NULL, status TEXT DEFAULT NULL, effective_from DATE DEFAULT NULL, remarks TEXT DEFAULT NULL, scode TEXT DEFAULT NULL, updated_by TEXT DEFAULT NULL, updated_on TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('CREATE TABLE associate_exit (exit_associate_id VARCHAR(255) NOT NULL, id SERIAL NOT NULL, exit_photo TEXT DEFAULT NULL, remarks TEXT DEFAULT NULL, asset_clearance BOOLEAN DEFAULT NULL, financial_clearance BOOLEAN DEFAULT NULL, security_clearance BOOLEAN DEFAULT NULL, hr_clearance BOOLEAN DEFAULT NULL, work_clearance BOOLEAN DEFAULT NULL, legal_clearance BOOLEAN DEFAULT NULL, exit_interview TEXT DEFAULT NULL, exit_initiated_by VARCHAR(255) DEFAULT NULL, exit_initiated_on TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, exit_submitted_by VARCHAR(255) DEFAULT NULL, exit_submitted_on TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, exit_date_time TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, otp_associate TEXT DEFAULT NULL, otp_center_incharge TEXT DEFAULT NULL, exit_gen_otp_associate TEXT DEFAULT NULL, exit_gen_otp_center_incharge TEXT DEFAULT NULL, exit_flag TEXT DEFAULT NULL, ip_address VARCHAR(255) DEFAULT NULL, PRIMARY KEY(exit_associate_id))');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('CREATE TABLE gps (itemid TEXT NOT NULL, date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, itemname TEXT DEFAULT NULL, quantity TEXT DEFAULT NULL, remarks TEXT DEFAULT NULL, collectedby TEXT DEFAULT NULL, itemtype TEXT DEFAULT NULL, taggedto TEXT DEFAULT NULL, lastupdatedon TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, asset_status TEXT DEFAULT NULL, PRIMARY KEY(itemid))');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('CREATE TABLE qpaper_qpaper (__hevo_id INT NOT NULL, name VARCHAR(512) DEFAULT NULL, date TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, qpaper VARCHAR(512) DEFAULT NULL, __hevo__ingested_at BIGINT DEFAULT NULL, __hevo__marked_deleted BOOLEAN DEFAULT NULL, associatenumber VARCHAR(512) DEFAULT NULL, PRIMARY KEY(__hevo_id))');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('CREATE TABLE ipf_history (goalsheetid TEXT NOT NULL, ipf NUMERIC(10, 0) DEFAULT NULL, ipf_response TEXT DEFAULT NULL, ipf_response_by TEXT DEFAULT NULL, ipf_response_on TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(goalsheetid))');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('CREATE TABLE payslip_component (component_id SERIAL NOT NULL, payslip_entry_id VARCHAR(255) DEFAULT NULL, components VARCHAR(255) DEFAULT NULL, subcategory VARCHAR(255) DEFAULT NULL, amount NUMERIC(10, 2) DEFAULT NULL, PRIMARY KEY(component_id))');
        $this->addSql('CREATE INDEX IDX_E42A77769682E28 ON payslip_component (payslip_entry_id)');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('CREATE TABLE payslip (payslipid TEXT NOT NULL, associatenumber TEXT DEFAULT NULL, fullname TEXT DEFAULT NULL, email TEXT DEFAULT NULL, profile TEXT DEFAULT NULL, transaction_id TEXT DEFAULT NULL, designation TEXT DEFAULT NULL, pan TEXT DEFAULT NULL, bankname TEXT DEFAULT NULL, ifsc TEXT DEFAULT NULL, accno TEXT DEFAULT NULL, dayspaid TEXT DEFAULT NULL, date TEXT DEFAULT NULL, sl TEXT DEFAULT NULL, cl TEXT DEFAULT NULL, location TEXT DEFAULT NULL, basebr TEXT DEFAULT NULL, deputebr TEXT DEFAULT NULL, basicsalaryar TEXT DEFAULT NULL, basicsalarycr TEXT DEFAULT NULL, miscar TEXT DEFAULT NULL, misccr TEXT DEFAULT NULL, overtimear TEXT DEFAULT NULL, overtimecr TEXT DEFAULT NULL, service_charges TEXT DEFAULT NULL, fines_penalties TEXT DEFAULT NULL, totale TEXT DEFAULT NULL, totald TEXT DEFAULT NULL, filename TEXT DEFAULT NULL, grade TEXT DEFAULT NULL, dateformat TEXT DEFAULT NULL, slno INT DEFAULT NULL, netpay NUMERIC(10, 0) DEFAULT NULL, PRIMARY KEY(payslipid))');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('CREATE TABLE donation (invoice TEXT NOT NULL, approvedby TEXT DEFAULT NULL, profile TEXT DEFAULT NULL, mergestatus TEXT DEFAULT NULL, "timestamp" TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, firstname TEXT DEFAULT NULL, emailaddress TEXT DEFAULT NULL, mobilenumber TEXT DEFAULT NULL, transactionid TEXT DEFAULT NULL, currencyofthedonatedamount TEXT DEFAULT NULL, additionalnote TEXT DEFAULT NULL, uinumber TEXT DEFAULT NULL, uitype TEXT DEFAULT NULL, address TEXT DEFAULT NULL, ack TEXT DEFAULT NULL, modeofpayment TEXT DEFAULT NULL, cauthenticationcode TEXT DEFAULT NULL, nameofitemsyoushared TEXT DEFAULT NULL, sauthenticationcode TEXT DEFAULT NULL, lastname TEXT DEFAULT NULL, youwantustospendyourdonationfor TEXT DEFAULT NULL, code TEXT DEFAULT NULL, filename TEXT DEFAULT NULL, dlastupdatedon TEXT DEFAULT NULL, id INT DEFAULT NULL, year TEXT DEFAULT NULL, donatedamount NUMERIC(10, 0) DEFAULT NULL, donation_type TEXT DEFAULT NULL, section_code TEXT DEFAULT NULL, date_of_donation TEXT DEFAULT NULL, PRIMARY KEY(invoice))');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('CREATE TABLE rolebasedgoal (id SERIAL NOT NULL, role_search TEXT DEFAULT NULL, parameter_1 TEXT DEFAULT NULL, expectation_1 TEXT DEFAULT NULL, max_rating_1 INT DEFAULT NULL, parameter_2 TEXT DEFAULT NULL, expectation_2 TEXT DEFAULT NULL, max_rating_2 INT DEFAULT NULL, parameter_3 TEXT DEFAULT NULL, expectation_3 TEXT DEFAULT NULL, max_rating_3 INT DEFAULT NULL, parameter_4 TEXT DEFAULT NULL, expectation_4 TEXT DEFAULT NULL, max_rating_4 INT DEFAULT NULL, parameter_5 TEXT DEFAULT NULL, expectation_5 TEXT DEFAULT NULL, max_rating_5 INT DEFAULT NULL, parameter_6 TEXT DEFAULT NULL, expectation_6 TEXT DEFAULT NULL, max_rating_6 INT DEFAULT NULL, parameter_7 TEXT DEFAULT NULL, expectation_7 TEXT DEFAULT NULL, max_rating_7 INT DEFAULT NULL, parameter_8 TEXT DEFAULT NULL, expectation_8 TEXT DEFAULT NULL, max_rating_8 INT DEFAULT NULL, parameter_9 TEXT DEFAULT NULL, expectation_9 TEXT DEFAULT NULL, max_rating_9 INT DEFAULT NULL, parameter_10 TEXT DEFAULT NULL, expectation_10 TEXT DEFAULT NULL, max_rating_10 INT DEFAULT NULL, parameter_11 TEXT DEFAULT NULL, expectation_11 TEXT DEFAULT NULL, max_rating_11 INT DEFAULT NULL, parameter_12 TEXT DEFAULT NULL, expectation_12 TEXT DEFAULT NULL, max_rating_12 INT DEFAULT NULL, parameter_13 TEXT DEFAULT NULL, expectation_13 TEXT DEFAULT NULL, max_rating_13 INT DEFAULT NULL, parameter_14 TEXT DEFAULT NULL, expectation_14 TEXT DEFAULT NULL, max_rating_14 INT DEFAULT NULL, parameter_15 TEXT DEFAULT NULL, expectation_15 TEXT DEFAULT NULL, max_rating_15 INT DEFAULT NULL, parameter_16 TEXT DEFAULT NULL, expectation_16 TEXT DEFAULT NULL, max_rating_16 INT DEFAULT NULL, parameter_17 TEXT DEFAULT NULL, expectation_17 TEXT DEFAULT NULL, max_rating_17 INT DEFAULT NULL, parameter_18 TEXT DEFAULT NULL, expectation_18 TEXT DEFAULT NULL, max_rating_18 INT DEFAULT NULL, parameter_19 TEXT DEFAULT NULL, expectation_19 TEXT DEFAULT NULL, max_rating_19 INT DEFAULT NULL, parameter_20 TEXT DEFAULT NULL, expectation_20 TEXT DEFAULT NULL, max_rating_20 INT DEFAULT NULL, PRIMARY KEY(id))');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('CREATE TABLE question (id SERIAL NOT NULL, category TEXT DEFAULT NULL, examname TEXT DEFAULT NULL, subject TEXT DEFAULT NULL, topic TEXT DEFAULT NULL, fullmarks TEXT DEFAULT NULL, year TEXT DEFAULT NULL, testcode TEXT DEFAULT NULL, url TEXT DEFAULT NULL, class TEXT DEFAULT NULL, flag TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('CREATE TABLE allocationdb_allocationdb (id SERIAL NOT NULL, hallocationdate VARCHAR(512) DEFAULT NULL, associatenumber VARCHAR(512) DEFAULT NULL, hfullname VARCHAR(512) DEFAULT NULL, hstatus VARCHAR(512) DEFAULT NULL, hmaxclass VARCHAR(512) DEFAULT NULL, hclasstaken VARCHAR(512) DEFAULT NULL, PRIMARY KEY(id))');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('CREATE TABLE rssimyaccount_members (associatenumber TEXT NOT NULL, doj TEXT DEFAULT NULL, fullname TEXT DEFAULT NULL, email TEXT DEFAULT NULL, basebranch TEXT DEFAULT NULL, gender TEXT DEFAULT NULL, dateofbirth TEXT DEFAULT NULL, howyouwouldliketobeaddressed TEXT DEFAULT NULL, currentaddress TEXT DEFAULT NULL, permanentaddress TEXT DEFAULT NULL, languagedetailsenglish TEXT DEFAULT NULL, languagedetailshindi TEXT DEFAULT NULL, workexperience TEXT DEFAULT NULL, nationalidentifier TEXT DEFAULT NULL, yourthoughtabouttheworkyouareengagedwith TEXT DEFAULT NULL, applicationnumber TEXT DEFAULT NULL, "position" TEXT DEFAULT NULL, approvedby TEXT DEFAULT NULL, associationstatus TEXT DEFAULT NULL, effectivedate TEXT DEFAULT NULL, remarks TEXT DEFAULT NULL, phone TEXT DEFAULT NULL, identifier TEXT DEFAULT NULL, astatus TEXT DEFAULT NULL, badge TEXT DEFAULT NULL, colors TEXT DEFAULT NULL, gm TEXT DEFAULT NULL, lastupdatedon TEXT DEFAULT NULL, photo TEXT DEFAULT NULL, mydoc TEXT DEFAULT NULL, class TEXT DEFAULT NULL, notification TEXT DEFAULT NULL, age TEXT DEFAULT NULL, depb TEXT DEFAULT NULL, attd TEXT DEFAULT NULL, filterstatus TEXT DEFAULT NULL, today TEXT DEFAULT NULL, allocationdate TEXT DEFAULT NULL, maxclass TEXT DEFAULT NULL, classtaken TEXT DEFAULT NULL, leave TEXT DEFAULT NULL, ctp TEXT DEFAULT NULL, feedback TEXT DEFAULT NULL, evaluationpath TEXT DEFAULT NULL, leaveapply TEXT DEFAULT NULL, cl TEXT DEFAULT NULL, sl TEXT DEFAULT NULL, el TEXT DEFAULT NULL, engagement TEXT DEFAULT NULL, cltaken TEXT DEFAULT NULL, sltaken TEXT DEFAULT NULL, eltaken TEXT DEFAULT NULL, othtaken TEXT DEFAULT NULL, clbal TEXT DEFAULT NULL, slbal TEXT DEFAULT NULL, elbal TEXT DEFAULT NULL, officialdoc TEXT DEFAULT NULL, profile TEXT DEFAULT NULL, filename TEXT DEFAULT NULL, fname TEXT DEFAULT NULL, quicklink TEXT DEFAULT NULL, yos TEXT DEFAULT NULL, role TEXT DEFAULT NULL, originaldoj TEXT DEFAULT NULL, iddoc TEXT DEFAULT NULL, vaccination TEXT DEFAULT NULL, scode TEXT DEFAULT NULL, exitinterview TEXT DEFAULT NULL, questionflag TEXT DEFAULT NULL, googlechat TEXT DEFAULT NULL, adjustedleave TEXT DEFAULT NULL, ipfl TEXT DEFAULT NULL, eduq TEXT DEFAULT NULL, mjorsub TEXT DEFAULT NULL, disc TEXT DEFAULT NULL, hbday TEXT DEFAULT NULL, on_leave TEXT DEFAULT NULL, attd_pending TEXT DEFAULT NULL, approveddate TEXT DEFAULT NULL, password VARCHAR(225) DEFAULT NULL, password_updated_by TEXT DEFAULT NULL, password_updated_on TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, default_pass_updated_by TEXT DEFAULT NULL, default_pass_updated_on TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, job_type TEXT DEFAULT NULL, salary NUMERIC(10, 0) DEFAULT NULL, panno TEXT DEFAULT NULL, bankname TEXT DEFAULT NULL, accountnumber TEXT DEFAULT NULL, ifsccode TEXT DEFAULT NULL, grade TEXT DEFAULT NULL, PRIMARY KEY(associatenumber))');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('CREATE TABLE leavedb_leavedb (leaveid TEXT NOT NULL, "timestamp" TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, applicantid TEXT DEFAULT NULL, typeofleave TEXT DEFAULT NULL, doc TEXT DEFAULT NULL, status TEXT DEFAULT NULL, comment TEXT DEFAULT NULL, lyear TEXT DEFAULT NULL, creason TEXT DEFAULT NULL, fromdate DATE DEFAULT NULL, todate DATE DEFAULT NULL, reviewer_id TEXT DEFAULT NULL, reviewer_name TEXT DEFAULT NULL, appliedby TEXT DEFAULT NULL, applicantcomment TEXT DEFAULT NULL, days NUMERIC(10, 0) DEFAULT NULL, halfday NUMERIC(10, 0) DEFAULT NULL, ack NUMERIC(10, 0) DEFAULT NULL, PRIMARY KEY(leaveid))');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('CREATE TABLE attendance (sl_no SERIAL NOT NULL, user_id VARCHAR(50) NOT NULL, punch_in TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, ip_address VARCHAR(50) DEFAULT NULL, gps_location VARCHAR(100) DEFAULT NULL, recorded_by VARCHAR(50) DEFAULT NULL, date DATE NOT NULL, PRIMARY KEY(sl_no))');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('CREATE TABLE payslip_entry (payslip_entry_id VARCHAR(255) NOT NULL, employeeid VARCHAR(255) DEFAULT NULL, paymonth VARCHAR(255) DEFAULT NULL, payyear INT DEFAULT NULL, dayspaid INT DEFAULT NULL, comment TEXT DEFAULT NULL, payslip_issued_by TEXT DEFAULT NULL, payslip_issued_on TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, payslip_issued_ip TEXT DEFAULT NULL, PRIMARY KEY(payslip_entry_id))');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('CREATE TABLE ipfsubmission (id SERIAL NOT NULL, "timestamp" TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, memberid2 TEXT DEFAULT NULL, membername2 TEXT DEFAULT NULL, ipf TEXT DEFAULT NULL, ipfinitiate TEXT DEFAULT NULL, status2 TEXT DEFAULT NULL, respondedon TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, ipfstatus TEXT DEFAULT NULL, closedon TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('CREATE TABLE medimate (id SERIAL NOT NULL, "timestamp" TEXT DEFAULT NULL, name TEXT DEFAULT NULL, registrationid TEXT DEFAULT NULL, mobilenumber TEXT DEFAULT NULL, email TEXT DEFAULT NULL, selectbeneficiary TEXT DEFAULT NULL, ageofbeneficiary TEXT DEFAULT NULL, bankname TEXT DEFAULT NULL, accountnumber TEXT DEFAULT NULL, accountholdername TEXT DEFAULT NULL, ifsccode TEXT DEFAULT NULL, clinicname TEXT DEFAULT NULL, clinicpincode TEXT DEFAULT NULL, doctorregistrationno TEXT DEFAULT NULL, nameoftreatingdoctor TEXT DEFAULT NULL, natureofillnessdiseaseaccident TEXT DEFAULT NULL, treatmentstartdate TEXT DEFAULT NULL, treatmentenddate TEXT DEFAULT NULL, billtype TEXT DEFAULT NULL, billnumber TEXT DEFAULT NULL, totalbillamount TEXT DEFAULT NULL, gstdlno TEXT DEFAULT NULL, uploadeddocuments TEXT DEFAULT NULL, uploadeddocumentscheck TEXT DEFAULT NULL, ack TEXT DEFAULT NULL, termsofagreement TEXT DEFAULT NULL, year TEXT DEFAULT NULL, claimid TEXT DEFAULT NULL, mergestatus TEXT DEFAULT NULL, claimstatus TEXT DEFAULT NULL, approvedamount TEXT DEFAULT NULL, transactionid TEXT DEFAULT NULL, transfereddate TEXT DEFAULT NULL, closedon TEXT DEFAULT NULL, mediremarks TEXT DEFAULT NULL, profile TEXT DEFAULT NULL, mlastupdatedon TEXT DEFAULT NULL, PRIMARY KEY(id))');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('DROP TABLE candidatepool');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('DROP TABLE donation_userdata');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('DROP TABLE test');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('DROP TABLE new_result');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('DROP TABLE donation_paymentdata');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('DROP TABLE leaveadjustment');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('DROP TABLE appraisee_response');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('DROP TABLE userlog_member');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('DROP TABLE asset');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('DROP TABLE visitor');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('DROP TABLE result');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('DROP TABLE wbt_status');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('DROP TABLE rssimyprofile_student');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('DROP TABLE claim');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('DROP TABLE gems');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('DROP TABLE leaveallocation');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('DROP TABLE certificate');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('DROP TABLE gps_history');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('DROP TABLE bookdata_book');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('DROP TABLE wbt');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('DROP TABLE fees');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('DROP TABLE policy');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('DROP TABLE notice');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('DROP TABLE stock_item');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('DROP TABLE myappraisal_myappraisal');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('DROP TABLE onboarding');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('DROP TABLE student');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('DROP TABLE associate_exit');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('DROP TABLE gps');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('DROP TABLE qpaper_qpaper');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('DROP TABLE ipf_history');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('DROP TABLE payslip_component');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('DROP TABLE payslip');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('DROP TABLE donation');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('DROP TABLE rolebasedgoal');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('DROP TABLE question');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('DROP TABLE allocationdb_allocationdb');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('DROP TABLE rssimyaccount_members');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('DROP TABLE leavedb_leavedb');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('DROP TABLE attendance');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('DROP TABLE payslip_entry');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('DROP TABLE ipfsubmission');
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQL100Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\PostgreSQL100Platform'."
        );

        $this->addSql('DROP TABLE medimate');
    }
}
