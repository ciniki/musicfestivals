#
# Description
# -----------
# This table stores the categories for accolades.
#
# Fields
# ------
# id:                       The ID assigned to the record.
# uuid:                     The Universal Unique ID.
# tnid:                     The Tenant ID the record belongs to.
#
# name:                     The name of the category.
# permalink:                The permalink for the category.
#
# flags:                    The options for the category.
#
#                               0x01 - Visible on website
#                               0x02 - Display winners
#                               0x04 - 
#                               0x08 - 
#                               0x10 - 
#                               0x20 - 
#                               0x40 - 
#                               0x80 - 
#
# image_id:                 The ID of the image to use for the category. Used in image buttons.
# synopsis:                 The synopsis of the category.
# description:              The description to be shown on the website.
# 
# awarded_email_subject:    The email subject for the awarded email.
# awarded_email_content:    The email html template for the awarded email.
# awarded_pdf_content:      The html content to be used in PDF for awarded letter.
#
# teacher_email_subject:    The email subject for sending teachers their winners list.
# teacher_email_content:    The email html template for sending teachers their winners list.
#
# date_added:               The UTC date and time the record was added.
# last_updated:             The UTC date and time the record was last update.
#
create table ciniki_musicfestival_accolade_categories (
    id int not null auto_increment,
    uuid char(36) not null,
    tnid int not null,

    name varchar(250) not null,
    permalink varchar(250) not null,
    flags smallint unsigned not null,
    sequence tinyint unsigned not null,
    image_id int not null,
    synopsis varchar(250) not null,
    description text not null,
    awarded_email_subject varchar(250) not null,
    awarded_email_content text not null,
    awarded_pdf_content text not null,
    teacher_email_subject varchar(250) not null,
    teacher_email_content text not null,

    date_added datetime not null,
    last_updated datetime not null,
    primary key (id),
    unique index (uuid),
    index sync (tnid, uuid, last_updated),
    unique index name (tnid, permalink)
) ENGINE='InnoDB', COMMENT='v1.01';
