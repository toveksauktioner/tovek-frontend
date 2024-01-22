create table if not exists entAcl
(
    aclId      int unsigned auto_increment
        primary key,
    aclType    enum ('dao', 'view', 'layout', 'userGroup') not null,
    aclAroId   varchar(255)                                not null,
    aclAroType enum ('user', 'userGroup')                  not null,
    aclAcoKey  varchar(255)                                not null,
    aclAccess  enum ('deny', 'allow')                      not null
)
    engine = MyISAM
    charset = utf8mb3;

create index if not exists aclAcoId
    on entAcl (aclAroId, aclAroType);

create table if not exists entAco
(
    acoKey   varchar(255)                   not null
        primary key,
    acoType  enum ('dao', 'view', 'layout') not null,
    acoGroup varchar(255)                   not null
)
    engine = MyISAM
    charset = utf8mb3;

create table if not exists entAdminMessage
(
    messageId            int(10) auto_increment
        primary key,
    messageLabel         varchar(255)                not null,
    messageTitleTextId   varchar(255)                not null,
    messageContentTextId varchar(255)                not null,
    messageStatus        enum ('inactive', 'active') not null,
    messageCreated       datetime                    not null,
    messageUpdated       datetime                    not null
)
    engine = MyISAM
    charset = utf8mb3;

create table if not exists entAdminMessageText
(
    textId      int(10) not null
        primary key,
    textLangId  int(10) not null,
    textContent text    not null
)
    engine = MyISAM
    charset = utf8mb3;

create index if not exists textId
    on entAdminMessageText (textId);

create index if not exists textLangId
    on entAdminMessageText (textLangId);

create table if not exists entAdminMessageToUser
(
    userId      int                not null,
    messageId   int                not null,
    messageRead enum ('no', 'yes') not null,
    userAccept  enum ('no', 'yes') not null,
    created     datetime           not null,
    constraint `UNIQUE`
        unique (userId, messageId)
)
    engine = MyISAM
    charset = utf8mb3;

create table if not exists entAppEvent
(
    eventId               int unsigned auto_increment
        primary key,
    eventKey              varchar(255)                  not null,
    eventType             enum ('internal', 'external') not null,
    eventListener         varchar(255)                  not null,
    eventListenerPath     varchar(255)                  not null,
    eventListenerFunction varchar(255)                  not null
)
    engine = MyISAM
    charset = utf8mb3;

create index if not exists eventKey
    on entAppEvent (eventKey);

create table if not exists entArgoCron
(
    cronId               int(10) auto_increment
        primary key,
    cronLayoutKeyTrigger varchar(255)           not null,
    cronTimeInterval     int(10)                not null,
    cronType             enum ('file', 'event') not null,
    cronTypeRelation     varchar(255)           not null,
    cronLastRun          int                    not null,
    cronCreated          datetime               not null
)
    engine = MyISAM
    charset = utf8mb3;

create table if not exists entAuction
(
    auctionId                 int(10)                            not null
        primary key,
    auctionType               enum ('net', 'live') default 'net' not null,
    auctionInternalName       varchar(255)                       not null,
    auctionInternalProject    varchar(255)                       not null,
    auctionTitle              varchar(255)                       not null,
    auctionShortTitle         varchar(120)                       not null,
    auctionSummary            text                               not null,
    auctionDescription        text                               not null,
    auctionContactDescription text                               not null,
    auctionLocation           varchar(255)                       not null,
    auctionLastPayDate        date                               not null,
    auctionArchiveStatus      enum ('active', 'inactive')        not null,
    auctionStatus             enum ('inactive', 'active')        not null,
    auctionViewedCount        int(10)                            not null,
    auctionCreated            datetime                           not null,
    auctionUpdated            datetime                           not null
)
    engine = MyISAM
    charset = utf8mb3;

create index if not exists entAuction_status_index
    on entAuction (auctionId, auctionStatus);

create table if not exists entAuctionAddress
(
    addressId                     int(10) auto_increment
        primary key,
    addressTitle                  varchar(255)                 not null,
    addressAddress                varchar(255)                 not null,
    addressAddressDescription     text                         null,
    addressShowingSpecial         varchar(255)                 not null,
    addressShowingStart           datetime                     not null,
    addressShowingEnd             datetime                     not null,
    addressShowingInfo            text                         not null,
    addressCollectSpecial         varchar(255)                 not null,
    addressCollectStart           datetime                     not null,
    addressCollectEnd             datetime                     not null,
    addressCollectInfo            text                         not null,
    addressFreightHelp            enum ('no', 'yes', 'custom') null,
    addressFreightInfo            text                         null,
    addressFreightRequestLastDate date                         null,
    addressFreightSenderId        int                          null,
    addressForkliftHelp           enum ('yes', 'no', 'custom') not null,
    addressLoadingInfo            text                         null,
    addressHidden                 enum ('no', 'yes')           null,
    addressPreRegistration        varchar(255)                 null,
    addressPartId                 int unsigned                 not null
)
    engine = MyISAM
    charset = utf8mb3;

create table if not exists entAuctionAutoBid
(
    autoId        int(10) auto_increment
        primary key,
    autoMaxBid    float              not null,
    autoPlaced    decimal(14, 4)     not null,
    autoCreated   datetime           not null,
    autoRemoved   enum ('no', 'yes') not null,
    autoAuctionId int(10)            not null,
    autoPartId    int(10)            not null,
    autoItemId    int(10)            not null,
    autoUserId    int(10)            not null
)
    engine = MyISAM
    charset = utf8mb3;

create table if not exists entAuctionAutoBidArchive
(
    autoId        int(10) auto_increment
        primary key,
    autoMaxBid    float              not null,
    autoPlaced    decimal(14, 4)     not null,
    autoCreated   datetime           not null,
    autoRemoved   enum ('no', 'yes') not null,
    autoAuctionId int(10)            not null,
    autoPartId    int(10)            not null,
    autoItemId    int(10)            not null,
    autoUserId    int(10)            not null
)
    engine = MyISAM;

create table if not exists entAuctionBid
(
    bidId            int(10) auto_increment
        primary key,
    bidType          enum ('normal', 'auto')   not null,
    bidValue         bigint unsigned default 0 not null,
    bidTransactionId varchar(50)               not null,
    bidPlaced        decimal(14, 4)            not null,
    bidCreated       datetime                  not null,
    bidRemoved       enum ('no', 'yes')        not null,
    bidAuctionId     int(10)                   not null,
    bidPartId        int(10)                   not null,
    bidItemId        int(10)                   not null,
    bidUserId        int(10)                   not null
)
    engine = MyISAM
    charset = utf8mb3;

create index if not exists `BID INDEX`
    on entAuctionBid (bidPlaced, bidItemId);

create table if not exists entAuctionBidArchive
(
    bidId            int(10) auto_increment
        primary key,
    bidType          enum ('normal', 'auto')   not null,
    bidValue         bigint unsigned default 0 not null,
    bidTransactionId varchar(50)               not null,
    bidPlaced        decimal(14, 4)            not null,
    bidCreated       datetime                  not null,
    bidRemoved       enum ('no', 'yes')        not null,
    bidAuctionId     int(10)                   not null,
    bidPartId        int(10)                   not null,
    bidItemId        int(10)                   not null,
    bidUserId        int(10)                   not null
)
    engine = MyISAM
    charset = utf8mb3;

create index if not exists `BID INDEX`
    on entAuctionBidArchive (bidPlaced, bidItemId);

create table if not exists entAuctionBidHistory
(
    historyId           int(10) auto_increment
        primary key,
    historyBidId        int(10)                   not null,
    historyBidType      varchar(255)              not null,
    historyBidValue     bigint unsigned default 0 not null,
    historyBidItemId    int(10)                   not null,
    historyBidUserId    int(10)                   not null,
    historyBidAuctionId int                       not null,
    historyBidPartId    int                       not null,
    historyBidPlaced    decimal(14, 4)            not null,
    historyCreated      datetime                  not null,
    historyExported     enum ('no', 'yes')        not null
)
    charset = utf8mb3;

create table if not exists entAuctionBidHistoryArchive
(
    historyId           int(10) auto_increment
        primary key,
    historyBidId        int(10)                   not null,
    historyBidType      varchar(255)              not null,
    historyBidValue     bigint unsigned default 0 not null,
    historyBidItemId    int(10)                   not null,
    historyBidUserId    int(10)                   not null,
    historyBidAuctionId int                       not null,
    historyBidPartId    int                       not null,
    historyBidPlaced    decimal(14, 4)            not null,
    historyCreated      datetime                  not null,
    historyExported     enum ('no', 'yes')        not null
)
    charset = utf8mb3;

create table if not exists entAuctionBidTariff
(
    breakValue   int(10)  not null
        primary key,
    minBidValue  int(10)  not null,
    maxBidValue  int(10)  not null,
    breakCreated datetime not null,
    breakUpdated datetime not null
)
    engine = MyISAM
    charset = utf8mb3;

create index if not exists breakValue
    on entAuctionBidTariff (breakValue);

create index if not exists maxBidValue
    on entAuctionBidTariff (maxBidValue);

create index if not exists minBidValue
    on entAuctionBidTariff (minBidValue);

create table if not exists entAuctionItem
(
    itemId                    int(10)                                           not null
        primary key,
    itemSortNo                int(10)                                           not null,
    itemSortLetter            varchar(255)                                      not null,
    itemTitle                 varchar(255)                                      not null,
    itemSummary               text                                              not null,
    itemDescription           text                                              not null,
    itemInformation           text                                              not null,
    itemLocation              varchar(255)                                      not null,
    itemYoutubeLink           varchar(255)                                      null,
    itemWinningBidValue       float                                             not null,
    itemWinningUserId         int(10)                                           not null,
    itemWinnerMailed          enum ('no', 'yes')                                not null,
    itemWinningBidId          int(10)                                           not null,
    itemBidCount              int(10)                                           not null,
    itemStatus                enum ('inactive', 'active', 'ended', 'cancelled') not null,
    itemEndTime               datetime                                          not null,
    itemMinBid                float                                             not null,
    itemFeeType               enum ('none', 'percent', 'sek')                   not null,
    itemFeeValue              float                                             not null,
    itemVatValue              int(10)                                           not null,
    itemMarketValue           float                                             not null,
    itemRecalled              enum ('yes', 'no')                                not null,
    itemHot                   enum ('no', 'yes')                                not null,
    itemViewedCount           int(10)                                           not null,
    itemCreated               datetime                                          not null,
    itemComment               varchar(255)                                      not null,
    itemNeedsAttention        enum ('no', 'yes')                                not null,
    itemCreatedByUserId       int(10)                                           not null,
    itemAuctionId             int(10)                                           not null,
    itemPartId                int(10)                                           not null,
    itemSubmissionId          int(10)                                           not null,
    itemSubmissionCustomId    varchar(255)                                      not null,
    itemAddressId             int(10)                                           not null,
    itemOldItemId             int(10)                                           not null,
    itemCopiedToItemId        int(10)                                           not null,
    itemVehicleArchiveImageId int(10)                                           not null,
    itemVehicleDataId         int(10)                                           not null,
    itemAutoBidLocked         enum ('no', 'yes')                                not null
)
    engine = MyISAM
    charset = utf8mb3;

create index if not exists entAuctionItem_itemAuctionId_index
    on entAuctionItem (itemAuctionId);

create index if not exists entAuctionItem_itemPartId_index
    on entAuctionItem (itemPartId);

create index if not exists entAuctionItem_itemSortNo_index
    on entAuctionItem (itemSortNo);

create index if not exists entAuctionItem_itemStatus_index
    on entAuctionItem (itemStatus);

create table if not exists entAuctionItemArchive
(
    itemId                    int(10)                                           not null
        primary key,
    itemSortNo                int(10)                                           not null,
    itemSortLetter            varchar(255)                                      not null,
    itemTitle                 varchar(255)                                      not null,
    itemSummary               text                                              not null,
    itemDescription           text                                              not null,
    itemInformation           text                                              not null,
    itemLocation              varchar(255)                                      not null,
    itemYoutubeLink           varchar(255)                                      null,
    itemWinningBidValue       float                                             not null,
    itemWinningUserId         int(10)                                           not null,
    itemWinnerMailed          enum ('no', 'yes')                                not null,
    itemWinningBidId          int(10)                                           not null,
    itemBidCount              int(10)                                           not null,
    itemStatus                enum ('inactive', 'active', 'ended', 'cancelled') not null,
    itemEndTime               datetime                                          not null,
    itemMinBid                float                                             not null,
    itemFeeType               enum ('none', 'percent', 'sek')                   not null,
    itemFeeValue              float                                             not null,
    itemVatValue              int(10)                                           not null,
    itemMarketValue           float                                             not null,
    itemRecalled              enum ('yes', 'no')                                not null,
    itemHot                   enum ('no', 'yes')                                not null,
    itemViewedCount           int(10)                                           not null,
    itemCreated               datetime                                          not null,
    itemComment               varchar(255)                                      not null,
    itemNeedsAttention        enum ('no', 'yes')                                not null,
    itemCreatedByUserId       int(10)                                           not null,
    itemAuctionId             int(10)                                           not null,
    itemPartId                int(10)                                           not null,
    itemSubmissionId          int(10)                                           not null,
    itemSubmissionCustomId    varchar(255)                                      not null,
    itemAddressId             int(10)                                           not null,
    itemOldItemId             int(10)                                           not null,
    itemCopiedToItemId        int(10)                                           not null,
    itemVehicleArchiveImageId int(10)                                           not null,
    itemVehicleDataId         int(10)                                           not null,
    itemAutoBidLocked         enum ('no', 'yes')                                not null
)
    engine = MyISAM
    charset = utf8mb3;

create index if not exists entAuctionItemArchive_itemAuctionId_index
    on entAuctionItemArchive (itemAuctionId);

create index if not exists entAuctionItemArchive_itemPartId_index
    on entAuctionItemArchive (itemPartId);

create index if not exists entAuctionItemArchive_itemSortNo_index
    on entAuctionItemArchive (itemSortNo);

create index if not exists entAuctionItemArchive_itemStatus_index
    on entAuctionItemArchive (itemStatus);

create table if not exists entAuctionItemToItem
(
    relationId       int(10) auto_increment
        primary key,
    relationFromId   int(10)      not null,
    relationFromType varchar(255) not null,
    relationToId     int(10)      not null,
    relationToType   varchar(255) not null,
    relationCreated  datetime     not null
)
    engine = MyISAM
    charset = utf8mb3;

create index if not exists relationFromId
    on entAuctionItemToItem (relationFromId);

create index if not exists relationToId
    on entAuctionItemToItem (relationToId);

create table if not exists entAuctionItemToUser
(
    itemId       int(10)           not null,
    userId       int(10)           not null,
    relationType enum ('favorite') not null
)
    engine = MyISAM
    charset = utf8mb3;

create index if not exists itemId
    on entAuctionItemToUser (itemId, userId);

create table if not exists entAuctionPart
(
    partId            int(10)                                                                not null
        primary key,
    partTitle         varchar(255)                                                           not null,
    partAuctionTitle  varchar(255)                                                           null,
    partDescription   varchar(255)                                                           not null,
    partYoutubeLink   varchar(255)                                                           null,
    partLocation      varchar(255)                                                           not null,
    partPreBidding    enum ('no', 'yes')                                                     not null,
    partAuctionStart  datetime                                                               not null,
    partStatus        enum ('inactive', 'upcomming', 'running', 'halted', 'ending', 'ended') not null,
    partHaltedTime    datetime                                                               not null,
    partCreated       datetime                                                               not null,
    partReviewValue   varchar(5)                                                             not null,
    partReviewComment varchar(255)                                                           not null,
    partAuctionId     varchar(255)                                                           not null
)
    engine = MyISAM
    charset = utf8mb3;

create index if not exists entAuctionPart_staus_start_index
    on entAuctionPart (partId, partAuctionId, partStatus, partAuctionStart);

create table if not exists entAuctionSearch
(
    searchId      int auto_increment
        primary key,
    searchString  varchar(255) not null,
    searchUserId  int          not null,
    searchCreated datetime     not null
);

create table if not exists entAuctionTag
(
    tagId          int(10) auto_increment
        primary key,
    tagTitle       varchar(255) not null,
    tagDescription varchar(255) not null,
    tagItemCount   int(10)      not null,
    tagCreated     datetime     not null
)
    engine = MyISAM
    charset = utf8mb3;

create table if not exists entAuctionTagToItem
(
    relationId        int(10) auto_increment
        primary key,
    relationTagId     int(10)  not null,
    relationItemId    int(10)  not null,
    relationAuctionId int(10)  not null,
    relationCreated   datetime not null
)
    engine = MyISAM
    charset = utf8mb3;

create index if not exists relationAuctionId
    on entAuctionTagToItem (relationAuctionId);

create index if not exists relationItemId
    on entAuctionTagToItem (relationItemId);

create index if not exists relationTagId
    on entAuctionTagToItem (relationTagId);

create table if not exists entAuctionToUser
(
    auctionId       int(10)  not null,
    userId          int(10)  not null,
    relationCreated datetime not null
)
    engine = MyISAM
    charset = utf8mb3;

create index if not exists UNIQE
    on entAuctionToUser (auctionId, userId);

create table if not exists entAuctionTransfer
(
    transferId        int(10) auto_increment
        primary key,
    transferType      enum ('import', 'export') not null,
    transferAuctionId int(10)                   not null,
    transferStatus    enum ('running', 'done')  not null,
    transferCreated   datetime                  not null,
    transferUpdated   datetime                  not null
)
    engine = MyISAM
    charset = utf8mb3;

create table if not exists entConfig
(
    configKey      varchar(255) not null
        primary key,
    configValue    varchar(255) not null,
    configGroupKey varchar(255) not null
)
    engine = MyISAM
    charset = utf8mb3;

create index if not exists configGroupKey
    on entConfig (configGroupKey);

create table if not exists entContact
(
    contactId                  int unsigned auto_increment
        primary key,
    contactButtonTextId        int unsigned not null,
    contactSubmitMessageTextId int unsigned not null
)
    engine = MyISAM
    charset = utf8mb3;

create table if not exists entContactText
(
    textId      int unsigned auto_increment,
    textLangId  int unsigned not null,
    textContent text         not null,
    constraint `UNIQUE`
        unique (textId, textLangId)
)
    engine = MyISAM
    charset = utf8mb3;

create table if not exists entContinent
(
    continentCode char(2)      not null
        primary key,
    continentName varchar(255) not null
)
    engine = MyISAM
    charset = utf8mb3;

create table if not exists entContinentCountry
(
    countryId            int auto_increment
        primary key,
    countryContinentCode char(3)     not null,
    countryIsoCode2      char(2)     not null,
    countryIsoCode3      char(3)     not null,
    countryNumber        char(3)     not null,
    countryName          varchar(64) not null
)
    engine = MyISAM
    charset = utf8mb3;

create index if not exists countries_name
    on entContinentCountry (countryName);

create table if not exists entContinentGroup
(
    entryId                      int unsigned auto_increment
        primary key,
    entryGroupKey                varchar(255)                not null,
    entryCountryId               int                         not null,
    entryContinentCode           char(2)                     not null,
    entryLocalCountryTitleTextId varchar(255)                not null,
    entryStatus                  enum ('active', 'inactive') not null,
    entrySort                    int                         not null,
    entryCreated                 datetime                    not null
)
    engine = MyISAM
    charset = utf8mb3;

create table if not exists entContinentGroupText
(
    textId      int unsigned auto_increment
        primary key,
    textLangId  int  not null,
    textContent text not null
)
    engine = MyISAM
    charset = utf8mb3;

create table if not exists entCurrency
(
    currencyId      int unsigned auto_increment
        primary key,
    currencyCode    char(3)        not null,
    currencyTitle   varchar(255)   not null,
    currencyRate    float unsigned not null,
    currencyCreated datetime       not null
)
    engine = MyISAM
    charset = utf8mb3;

create index if not exists currencyCode
    on entCurrency (currencyCode);

create table if not exists entCustomer
(
    customerId          int(10) auto_increment
        primary key,
    customerNumber      int(10)            not null,
    customerDescription text               not null,
    customerBlacklisted enum ('no', 'yes') not null,
    customerUserId      int unsigned       not null,
    customerLastOrderId int unsigned       not null,
    customerCreated     datetime           not null
)
    engine = MyISAM
    charset = utf8mb3;

create index if not exists customerNumber
    on entCustomer (customerNumber);

create table if not exists entCustomerCategory
(
    categoryId                    int(10) auto_increment
        primary key,
    categoryTitleTextId           varchar(255)                            not null,
    categoryCanonicalUrlTextId    varchar(255)                            not null,
    categoryDescriptionTextId     varchar(255)                            not null,
    categoryCustomerBehavior      enum ('children', 'current', 'grouped') not null,
    categoryPageTitleTextId       varchar(255)                            not null,
    categoryPageDescriptionTextId varchar(255)                            not null,
    categoryPageKeywordsTextId    varchar(255)                            not null,
    categoryLeft                  int(10)                                 not null,
    categoryRight                 int(10)                                 not null,
    categoryCreated               datetime                                not null,
    categoryUpdated               datetime                                not null
)
    engine = MyISAM
    charset = utf8mb3;

create table if not exists entCustomerCredit
(
    creditId         int(10) auto_increment
        primary key,
    creditCustomerId int(10)                      not null,
    creditValue      float                        not null,
    creditValueType  enum ('credit', 'debt')      not null,
    creditStatus     enum ('available', 'locked') not null,
    creditCreated    datetime                     not null,
    creditUpdated    datetime                     not null,
    constraint creditCustomerId
        unique (creditCustomerId)
)
    engine = MyISAM
    charset = utf8mb3;

create table if not exists entCustomerCreditTransaction
(
    transactionId          int(10) auto_increment
        primary key,
    transactionCustomId    float                          not null,
    transactionValue       float                          not null,
    transactionType        enum ('deposit', 'withdrawal') not null,
    transactionDescription varchar(255)                   not null,
    transactionCreditId    int                            not null,
    transactionCreated     datetime                       not null
)
    engine = MyISAM
    charset = utf8mb3;

create table if not exists entCustomerGroup
(
    groupId               int(10) auto_increment
        primary key,
    groupNameTextId       int(10)            not null,
    groupVatInclusion     enum ('yes', 'no') not null,
    groupAutoGrantedUsage enum ('yes', 'no') not null,
    groupCreated          datetime           not null
)
    engine = MyISAM
    charset = utf8mb3;

create table if not exists entCustomerService
(
    serviceId                int(10) auto_increment
        primary key,
    serviceTitleTextId       varchar(255) not null,
    serviceDescriptionTextId varchar(255) not null,
    serviceCreated           datetime     not null,
    serviceUpdated           datetime     not null
)
    engine = MyISAM
    charset = utf8mb3;

create table if not exists entCustomerText
(
    textId      int(11) unsigned not null,
    textLangId  int              not null,
    textContent varchar(255)     not null,
    constraint `UNIQUE`
        unique (textId, textLangId)
)
    engine = MyISAM
    charset = utf8mb3;

create table if not exists entCustomerToCategory
(
    customerId int not null,
    categoryId int not null
)
    engine = MyISAM
    charset = utf8mb3;

create table if not exists entCustomerToCustomerGroup
(
    customerId int not null,
    groupId    int not null,
    constraint customerId
        unique (customerId, groupId)
)
    engine = MyISAM
    charset = utf8mb3;

create table if not exists entDashboardLink
(
    linkId          int(10) auto_increment
        primary key,
    linkTextSwedish varchar(255)                  not null,
    linkTextEnglish varchar(255)                  not null,
    linkUrl         varchar(255)                  not null,
    linkDescription varchar(255)                  not null,
    linkType        enum ('external', 'internal') not null,
    linkSort        int                           not null,
    linkCreated     datetime                      not null,
    linkUpdated     datetime                      not null
)
    engine = MyISAM
    charset = utf8mb3;

create table if not exists entEuropeanUnionCountries
(
    countryId            int auto_increment
        primary key,
    countryContinentCode char(3)     not null,
    countryIsoCode2      char(2)     not null,
    countryIsoCode3      char(3)     not null,
    countryNumber        char(3)     not null,
    countryName          varchar(64) not null
)
    engine = MyISAM
    charset = utf8mb3;

create index if not exists countries_name
    on entEuropeanUnionCountries (countryName);

create table if not exists entFaqCategory
(
    categoryId                int(10) auto_increment
        primary key,
    categoryTitleTextId       int                         not null,
    categoryDescriptionTextId int                         not null,
    categoryStatus            enum ('inactive', 'active') not null,
    categoryPublishStart      datetime                    not null,
    categoryPublishEnd        datetime                    not null,
    categorySort              int(10)                     not null,
    categoryCreated           datetime                    not null,
    categoryUpdated           datetime                    not null
)
    engine = MyISAM
    charset = utf8mb3;

create table if not exists entFaqQuestion
(
    questionId           int(10) auto_increment
        primary key,
    questionCategoryId   int(10)                     not null,
    questionTitleTextId  int                         not null,
    questionAnswerTextId int                         not null,
    questionStatus       enum ('active', 'inactive') not null,
    questionSort         int(10)                     not null,
    questionCreated      datetime                    not null,
    questionUpdated      datetime                    not null
)
    engine = MyISAM
    charset = utf8mb3;

create index if not exists questionCategoryId
    on entFaqQuestion (questionCategoryId);

create table if not exists entFaqText
(
    textId      int(10)      not null,
    textLangId  int(10)      not null,
    textContent varchar(255) not null,
    constraint `UNIQUE`
        unique (textId, textLangId)
)
    engine = MyISAM
    charset = utf8mb3;

create table if not exists entFile
(
    fileId         int unsigned auto_increment
        primary key,
    filename       varchar(255)     not null,
    fileExtension  varchar(10)      not null,
    fileType       varchar(255)     not null,
    fileParentType varchar(255)     not null,
    fileParentId   int unsigned     not null,
    fileTitle      varchar(255)     not null,
    fileSort       tinyint unsigned not null,
    fileCreated    datetime         not null
)
    engine = MyISAM
    charset = utf8mb3;

create index if not exists pictureParentType
    on entFile (fileParentType, fileParentId);

create table if not exists entFileAccess
(
    accessId            int(11) unsigned auto_increment
        primary key,
    accessFileId        int                        not null,
    accessUserId        int                        not null,
    accessStatus        enum ('allow', 'disallow') not null,
    accessCreated       datetime                   not null,
    accessUpdated       datetime                   not null,
    accessCreatorUserId int                        not null,
    accessUpdaterUserId int                        not null
)
    engine = MyISAM
    charset = utf8mb3;

create table if not exists entFinancing
(
    financingId              int unsigned auto_increment
        primary key,
    financingExternalOrderId varchar(255)                                                                                                      not null,
    financingInternalOrderId varchar(255)                                                                                                      null,
    financingCreated         datetime                                                                                                          null,
    financingUpdated         datetime                                                                                                          null,
    financingUserId          int                                                                                                               null,
    financingService         varchar(255)                                                                                    default ''        not null,
    financingStatus          enum ('created', 'initialized', 'pending', 'ready_to_ship', 'shipped', 'completed', 'canceled') default 'created' null,
    financingLocalStatus     enum ('pending', 'requested', 'handled', 'cancelled')                                           default 'pending' null,
    financingTotalValue      int                                                                                                               null,
    financingOrgNo           varchar(10)                                                                                                       null,
    financingCreditInvoiceId int                                                                                                               null
);

create table if not exists entFinancingServiceRequest
(
    requestId              int unsigned auto_increment
        primary key,
    requestFunction        varchar(255)      not null,
    requestQueryCreated    datetime          null,
    requestResponse        text              null,
    requestResponseCode    smallint unsigned null,
    requestResponseCreated datetime          null,
    requestQuery           text              null
);

create table if not exists entFinancingToItem
(
    financingId    int not null,
    itemId         int not null,
    userId         int null,
    requestedValue int null,
    primary key (financingId, itemId)
);

create table if not exists entFreight
(
    freightId        int unsigned auto_increment
        primary key,
    freightCountryId int unsigned not null,
    freightValue     float        not null,
    freightCreated   datetime     not null,
    constraint freightCountryId
        unique (freightCountryId)
)
    engine = MyISAM
    charset = utf8mb3;

create table if not exists entFreightCurrency
(
    entryId                      int unsigned auto_increment
        primary key,
    entryFreightTypeId           int(10)                     not null,
    entryCurrencyId              int(10)                     not null,
    entryFreightCurrencyAddition float                       not null,
    entryStatus                  enum ('active', 'inactive') not null,
    entryCreated                 datetime                    not null,
    entryUpdated                 datetime                    not null
)
    engine = MyISAM
    charset = utf8mb3;

create table if not exists entFreightFreeLimitToCountry
(
    freightFreeLimit float not null,
    countryId        int   not null
)
    engine = MyISAM
    charset = utf8mb3;

create index if not exists freightId
    on entFreightFreeLimitToCountry (freightFreeLimit, countryId);

create table if not exists entFreightRelation
(
    relationId               int unsigned auto_increment
        primary key,
    relationFreightTypeId    int                        not null,
    relationProductId        int                        not null,
    relationFreightPriceType enum ('fixed', 'elevated') not null,
    relationElevatedPrice    float                      not null,
    relationStatus           enum ('valid', 'invalid')  not null
)
    engine = MyISAM
    charset = utf8mb3;

create table if not exists entFreightType
(
    freightTypeId          int unsigned auto_increment
        primary key,
    freightTypeTitle       varchar(255)                   not null,
    freightTypeDescription text                           not null,
    freightTypeAdnlInfo    enum ('no', 'yes', 'required') not null,
    freightTypePrice       float                          not null,
    freightTypeSort        int                            not null,
    freightTypeStatus      enum ('active', 'inactive')    not null,
    freightTypeCreated     datetime                       not null
)
    engine = MyISAM
    charset = utf8mb3;

create index if not exists freightTypeStatus
    on entFreightType (freightTypeStatus);

create table if not exists entFreightTypeToCountry
(
    freightTypeId int not null,
    countryId     int not null
)
    engine = MyISAM
    charset = utf8mb3;

create index if not exists freightId
    on entFreightTypeToCountry (freightTypeId, countryId);

create table if not exists entFreightTypeToCustomerGroup
(
    relationId    int auto_increment
        primary key,
    freightTypeId int          not null,
    groupId       varchar(255) not null,
    constraint freightTypeId_2
        unique (freightTypeId, groupId)
)
    engine = MyISAM
    charset = utf8mb3;

create table if not exists entFreightWeight
(
    freightWeightId       int(10) auto_increment
        primary key,
    freightTypeId         int(10)                    not null,
    freightWeightFrom     int(10)                    not null,
    freightWeightTo       int(10)                    not null,
    freightWeightAddition float                      not null,
    freightWeightStatus   enum ('allowed', 'denied') not null,
    freightWeightUpdated  datetime                   not null,
    freightWeightCreated  datetime                   not null
)
    engine = MyISAM
    charset = utf8mb3;

create index if not exists freightTypeId
    on entFreightWeight (freightTypeId);

create index if not exists freightTypeId_2
    on entFreightWeight (freightTypeId, freightWeightFrom, freightWeightTo);

create index if not exists freightWeightFrom
    on entFreightWeight (freightWeightFrom);

create index if not exists freightWeightStatus
    on entFreightWeight (freightWeightStatus);

create index if not exists freightWeightTo
    on entFreightWeight (freightWeightTo);

create table if not exists entHelpCategory
(
    helpCategoryId                int auto_increment
        primary key,
    helpCategoryIcon              varchar(255)                null,
    helpCategoryTitleTextId       int                         null,
    helpCategoryDescriptionTextId int                         null,
    helpCategoryParentId          int                         null,
    helpCategoryStatus            enum ('inactive', 'active') null,
    helpCategoryPublishStart      datetime                    null,
    helpCategoryPublishEnd        datetime                    null,
    helpCategorySort              int                         null,
    helpCategoryCreated           datetime                    null,
    helpCategoryUpdated           datetime                    null
)
    charset = utf8mb3;

create table if not exists entHelpText
(
    textId      int(10) not null,
    textLangId  int(10) not null,
    textContent text    not null,
    constraint `UNIQUE`
        unique (textId, textLangId)
)
    charset = utf8mb3;

create table if not exists entHelpTopic
(
    helpTopicId                int auto_increment
        primary key,
    helpTopicTitleTextId       int                         null,
    helpTopicDescriptionTextId int                         null,
    helpTopicStatus            enum ('inactive', 'active') null,
    helpTopicPublishStart      datetime                    null,
    helpTopicPublishEnd        datetime                    null,
    helpTopicCreated           datetime                    null,
    helpTopicUpdated           datetime                    null
)
    charset = utf8mb3;

create table if not exists entHelpTopicRelation
(
    helpTopicId         int null,
    helpTopicRelationId int not null
        primary key,
    constraint entHelpTopicRelation_entHelpTopic_helpTopicId_fk
        foreign key (helpTopicId) references entHelpTopic (helpTopicId)
            on delete cascade
)
    charset = utf8mb3;

create table if not exists entHelpTopicToCategory
(
    helpTopicId    int not null,
    helpCategoryId int not null,
    constraint entHelpTopicToCategory_helpTopicId_helpCategoryId_uindex
        unique (helpTopicId, helpCategoryId),
    constraint entHelpTopicToCategory_entHelpCategory_helpCategoryId_fk
        foreign key (helpCategoryId) references entHelpCategory (helpCategoryId)
            on delete cascade,
    constraint entHelpTopicToCategory_entHelpTopic_helpTopicId_fk
        foreign key (helpTopicId) references entHelpTopic (helpTopicId)
            on delete cascade
)
    charset = utf8mb3;

create table if not exists entImage
(
    imageId              int unsigned auto_increment
        primary key,
    imageFileExtension   varchar(255)     not null,
    imageParentType      varchar(255)     not null,
    imageParentId        int unsigned     not null,
    imageKey             varchar(256)     not null,
    imageAlternativeText varchar(255)     not null,
    imageMD5             varchar(32)      not null,
    imageSort            tinyint unsigned not null,
    imageCreated         datetime         not null,
    imageObjectStorageId int              null
)
    engine = MyISAM
    charset = utf8mb3;

create index if not exists imageKey
    on entImage (imageKey);

create index if not exists imageMD5
    on entImage (imageMD5);

create index if not exists pictureParentType
    on entImage (imageParentType, imageParentId);

create table if not exists entImageAltRoute
(
    entryId                         int(10) auto_increment
        primary key,
    entryImageId                    int(10)      not null,
    entryRouteId                    int(10)      not null,
    entryImageAlternativeTextTextId varchar(255) not null,
    entryCreated                    datetime     not null,
    entryUpdated                    datetime     not null
)
    engine = MyISAM
    charset = utf8mb3;

create table if not exists entImageText
(
    textId      int(10)      not null,
    textLangId  int(10)      not null,
    textContent varchar(255) not null,
    constraint `UNIQUE`
        unique (textId, textLangId)
)
    engine = MyISAM
    charset = utf8mb3;

create table if not exists entInfoContent
(
    contentId      int auto_increment
        primary key,
    contentTextId  int                                    not null,
    contentViewId  int                                    not null,
    contentKey     varchar(255)                           not null,
    contentStatus  enum ('active', 'preview', 'inactive') not null,
    contentUpdated datetime                               not null,
    contentCreated datetime                               not null
)
    engine = MyISAM
    charset = utf8mb3;

create index if not exists contentStatus
    on entInfoContent (contentStatus);

create table if not exists entInfoContentRevision
(
    revisionId      int unsigned auto_increment
        primary key,
    contentId       int(10)      not null,
    revisionLangId  int unsigned not null,
    userId          int unsigned not null,
    username        varchar(50)  not null,
    revisionContent text         not null,
    revisionCreated text         not null
)
    engine = MyISAM
    charset = utf8mb3;

create index if not exists contentId
    on entInfoContentRevision (contentId);

create index if not exists revisionLangId
    on entInfoContentRevision (revisionLangId);

create index if not exists userId
    on entInfoContentRevision (userId, username);

create table if not exists entInfoContentText
(
    textId      int,
    textLangId  int  not null,
    textContent text not null
)
    engine = MyISAM
    charset = utf8mb3;

create index if not exists textId
    on entInfoContentText (textId);

alter table entInfoContentText
    modify textId int auto_increment;

create table if not exists entLayout
(
    layoutKey                  varchar(255)       not null
        primary key,
    layoutFile                 varchar(255)       not null,
    layoutTemplateFile         varchar(255)       not null,
    layoutTitleTextId          int unsigned       not null,
    layoutKeywordsTextId       int unsigned       not null,
    layoutDescriptionTextId    int unsigned       not null,
    layoutCanonicalUrlTextId   int unsigned       not null,
    layoutSuffixContent        text               not null,
    layoutBodyClass            varchar(360)       not null,
    layoutProtected            enum ('no', 'yes') not null,
    layoutDynamicChildrenRoute enum ('no', 'yes') not null,
    layoutCreated              datetime           not null,
    layoutUpdated              datetime           not null
)
    engine = MyISAM
    charset = utf8mb3;

create table if not exists entLayoutSection
(
    sectionId        int unsigned auto_increment
        primary key,
    sectionKey       varchar(255) not null,
    sectionLayoutKey varchar(255) not null
)
    engine = MyISAM
    charset = utf8mb3;

create index if not exists sectionLayoutKey
    on entLayoutSection (sectionLayoutKey);

create table if not exists entLayoutText
(
    textId      int unsigned,
    textLangId  int unsigned not null,
    textContent text         not null
)
    engine = MyISAM
    charset = utf8mb3;

create index if not exists textId
    on entLayoutText (textId);

create index if not exists textLangId
    on entLayoutText (textLangId);

alter table entLayoutText
    modify textId int unsigned auto_increment;

create table if not exists entLocale
(
    localeId              mediumint unsigned auto_increment
        primary key,
    localeCode            varchar(7)                                                        not null,
    localeTitle           varchar(50)                                                       not null,
    localeDefaultMonetary varchar(32)                                                       not null,
    localeDefaultCurrency varchar(8)                                                        not null,
    localeUse             enum ('language', 'money', 'both', 'inactive') default 'inactive' not null,
    localeSort            int unsigned                                                      not null
)
    engine = MyISAM
    charset = utf8mb3;

create index if not exists localeSort
    on entLocale (localeSort);

create index if not exists localeUse
    on entLocale (localeUse);

create table if not exists entLog
(
    logId      int(20) unsigned auto_increment
        primary key,
    logLabel   varchar(255) not null,
    logData    text         not null,
    logCreated datetime     not null
)
    engine = MyISAM
    charset = utf8mb3;

create table if not exists entNavigation
(
    navigationId       int unsigned,
    navigationLangId   int unsigned           not null,
    navigationGroupKey varchar(255)           not null,
    navigationUrl      varchar(255)           not null,
    navigationTitle    varchar(255)           not null,
    navigationImageSrc varchar(255)           not null,
    navigationOpenIn   enum ('self', 'blank') not null,
    navigationLeft     int unsigned           not null,
    navigationRight    int unsigned           not null
)
    engine = MyISAM
    charset = utf8mb3;

create index if not exists navGroupId
    on entNavigation (navigationGroupKey, navigationLeft);

create index if not exists navigationId
    on entNavigation (navigationId);

create index if not exists navigationLangId
    on entNavigation (navigationLangId);

alter table entNavigation
    modify navigationId int unsigned auto_increment;

create table if not exists entNews
(
    newsId              int unsigned auto_increment
        primary key,
    newsTitleTextId     int(10)                     not null,
    newsSummaryTextId   int(10)                     not null,
    newsContentTextId   int(10)                     not null,
    newsMetaKeywords    int unsigned                not null,
    newsMetaDescription int unsigned                not null,
    newsStatus          enum ('inactive', 'active') not null,
    newsPublishStart    datetime                    not null,
    newsPublishEnd      datetime                    not null,
    newsCreated         datetime                    not null,
    newsUpdated         datetime                    not null
)
    engine = MyISAM
    charset = utf8mb3;

create index if not exists newsCreated
    on entNews (newsCreated);

create index if not exists newsPublishEnd
    on entNews (newsPublishEnd);

create index if not exists newsPublishStart
    on entNews (newsPublishStart);

create index if not exists newsSummary
    on entNews (newsSummaryTextId);

create index if not exists newsTitle
    on entNews (newsTitleTextId);

create table if not exists entNewsText
(
    textId      int unsigned not null,
    textLangId  int unsigned not null,
    textContent text         not null,
    constraint `UNIQUE`
        unique (textId, textLangId)
)
    engine = MyISAM
    charset = utf8mb3;

create table if not exists entPayment
(
    paymentId                int(11) unsigned auto_increment
        primary key,
    paymentTitleTextId       int(11) unsigned                      not null,
    paymentDescriptionTextId int(11) unsigned                      not null,
    paymentPriceAllowed      enum ('yes', 'no')                    not null,
    paymentPrice             float                                 not null,
    paymentStatus            enum ('inactive', 'active')           not null,
    paymentClass             varchar(255)                          not null,
    paymentType              enum ('providerHosted', 'inSiteView') not null,
    paymentSort              tinyint unsigned                      not null,
    paymentCreated           datetime                              not null
)
    engine = MyISAM
    charset = utf8mb3;

create table if not exists entPaymentText
(
    textId      int unsigned,
    textLangId  int unsigned not null,
    textContent text         not null
)
    engine = MyISAM
    charset = utf8mb3;

create index if not exists textId
    on entPaymentText (textId);

alter table entPaymentText
    modify textId int unsigned auto_increment;

create table if not exists entPaymentToCountry
(
    paymentId int not null,
    countryId int not null
)
    engine = MyISAM
    charset = utf8mb3;

create index if not exists paymentId
    on entPaymentToCountry (paymentId, countryId);

create table if not exists entPaymentToCustomerGroup
(
    relationId int auto_increment
        primary key,
    paymentId  int not null,
    groupId    int not null,
    constraint paymentId_2
        unique (paymentId, groupId)
)
    engine = MyISAM
    charset = utf8mb3;

create table if not exists entPaymentToFreightType
(
    relationId    int unsigned auto_increment
        primary key,
    paymentId     int not null,
    freightTypeId int not null
)
    engine = MyISAM
    charset = utf8mb3;

create index if not exists paymentId
    on entPaymentToFreightType (paymentId, freightTypeId);

create table if not exists entPaymentToOrderField
(
    paymentId  int          not null,
    orderField varchar(255) not null,
    constraint paymentId
        unique (paymentId, orderField)
)
    engine = MyISAM
    charset = utf8mb3;

create table if not exists entPuff
(
    puffId                 int unsigned auto_increment
        primary key,
    puffLayoutKey          varchar(50)                                                        not null,
    puffTitleTextId        varchar(255)                                                       not null,
    puffContentTextId      varchar(255)                                                       not null,
    puffShortContentTextId varchar(550)                                                       not null,
    puffUrlTextId          varchar(550)                                                       not null,
    puffClass              varchar(255)                                                       not null,
    puffStatus             enum ('active', 'inactive')                                        not null,
    puffPublishStart       datetime                                                           not null,
    puffPublishEnd         datetime                                                           not null,
    puffSort               int                                                                not null,
    puffUpdated            datetime                                                           not null,
    puffCreated            datetime                                                           not null,
    puffUserType           enum ('all', 'foreign', 'domestic', 'user', 'guest') default 'all' null
)
    engine = MyISAM
    charset = utf8mb3;

create table if not exists entPuffText
(
    textId      int unsigned not null,
    textLangId  int unsigned not null,
    textContent text         not null,
    constraint `UNIQUE`
        unique (textId, textLangId)
)
    engine = MyISAM
    charset = utf8mb3;

create table if not exists entRobotsTxt
(
    ruleId         int(11) unsigned auto_increment
        primary key,
    ruleType       enum ('user-agent', 'disallow', 'allow', 'sitemap')                         not null,
    ruleVariable   varchar(255)                                                                not null,
    ruleSort       int                                                                         not null,
    ruleCreated    datetime                                                                    not null,
    ruleActivation enum ('always', 'never', 'on-not-released', 'on-released') default 'always' not null
)
    engine = MyISAM
    charset = utf8mb3;

create table if not exists entRoute
(
    routeId         int unsigned auto_increment
        primary key,
    routeLayoutKey  varchar(255)               null,
    routePathLangId tinyint unsigned default 1 not null,
    routePath       varchar(255)               null,
    routeCreated    datetime                   not null,
    routeUpdated    datetime                   not null
)
    engine = MyISAM
    charset = utf8mb3;

create index if not exists routePath
    on entRoute (routePath);

create index if not exists routePathLangId
    on entRoute (routePathLangId);

create table if not exists entRouteArchive
(
    routeId         int unsigned auto_increment
        primary key,
    routeLayoutKey  varchar(255)               null,
    routePathLangId tinyint unsigned default 1 not null,
    routePath       varchar(255)               null,
    routeCreated    datetime                   not null,
    routeUpdated    datetime                   not null
)
    engine = MyISAM
    charset = utf8mb3;

create index if not exists routePath
    on entRouteArchive (routePath);

create index if not exists routePathLangId
    on entRouteArchive (routePathLangId);

create table if not exists entRouteHttpStatus
(
    statusId                int(10) auto_increment
        primary key,
    statusLayoutKey         varchar(255)                                                                                                                                                                                                                                                                                                                                                                                                  not null,
    statusLangId            int(10)                                                                                                                                                                                                                                                                                                                                                                                                       not null,
    statusDomain            varchar(255)                                                                                                                                                                                                                                                                                                                                                                                                  not null,
    statusRoutePath         varchar(255)                                                                                                                                                                                                                                                                                                                                                                                                  not null,
    statusCode              enum ('100', '101', '102', '200', '201', '202', '203', '204', '205', '206', '207', '300', '301', '302', '303', '304', '305', '306', '307', '400', '401', '402', '403', '404', '405', '406', '407', '408', '409', '410', '411', '412', '413', '414', '415', '416', '417', '418', '422', '423', '424', '425', '426', '449', '450', '451', '500', '501', '502', '503', '504', '505', '506', '507', '509', '510') not null,
    statusData              varchar(255)                                                                                                                                                                                                                                                                                                                                                                                                  not null,
    statusAddiditonalHeader varchar(255)                                                                                                                                                                                                                                                                                                                                                                                                  not null,
    statusContinueRequest   enum ('no', 'yes')                                                                                                                                                                                                                                                                                                                                                                                            not null
)
    engine = MyISAM
    charset = utf8mb3;

create index if not exists routeLayoutKey
    on entRouteHttpStatus (statusLayoutKey);

create index if not exists statusRoutePath
    on entRouteHttpStatus (statusRoutePath);

create table if not exists entRouteToObject
(
    routeId    int(10)      not null,
    objectId   int(10)      not null,
    objectType varchar(255) not null,
    constraint `UNIQUE`
        unique (routeId, objectId, objectType)
)
    engine = MyISAM
    charset = utf8mb3;

create index if not exists entRouteToObject_objectType_index
    on entRouteToObject (objectType);

create table if not exists entRouteToObjectArchive
(
    routeId    int(10)      not null,
    objectId   int(10)      not null,
    objectType varchar(255) not null,
    constraint `UNIQUE`
        unique (routeId, objectId, objectType)
)
    engine = MyISAM
    charset = utf8mb3;

create table if not exists entSessionStorage
(
    sessionId        varchar(255) default '' not null
        primary key,
    sessionLastIp    int(10)                 not null,
    sessionLastIpGeo varchar(255)            not null,
    sessionUserAgent varchar(400)            not null,
    sessionData      longtext                not null,
    sessionUserId    int(10)                 not null,
    sessionTimestamp int(10)                 not null
)
    engine = MyISAM
    charset = utf8mb3;

create table if not exists entSlideshowImage
(
    slideshowImageId              int unsigned auto_increment
        primary key,
    slideshowImageSort            int(10)                                        not null,
    slideshowImageStatus          enum ('active', 'inactive')                    not null,
    slideshowImageStart           datetime                                       not null,
    slideshowImageEnd             datetime                                       not null,
    slideshowImageContentTextId   int(11) unsigned                               not null,
    slideshowImageUrlTextId       int(11) unsigned                               not null,
    slideshowImageTextColor       varchar(7)                                     not null,
    slideshowImageBackgroundColor varchar(7)                                     not null,
    slideshowImageGradientColor   varchar(7)                                     not null,
    slideshowImageSpeed           varchar(255)                                   not null,
    slideshowImageTimeout         varchar(255)                                   not null,
    slideshowImageFx              enum ('fade', 'fadeout', 'scrollHorz', 'none') not null,
    slideshowImageCreated         datetime                                       not null,
    slideshowImageUpdated         datetime                                       not null
)
    engine = MyISAM
    charset = utf8mb3;

create index if not exists slideshowImageSort
    on entSlideshowImage (slideshowImageSort);

create index if not exists slideshowImageStatus
    on entSlideshowImage (slideshowImageStatus);

create table if not exists entSlideshowImageText
(
    textId      int(11) unsigned not null,
    textLangId  int(11) unsigned not null,
    textContent text             not null,
    constraint `UNIQUE`
        unique (textId, textLangId)
)
    engine = MyISAM
    charset = utf8mb3;

create table if not exists entSlideshowImageToLayout
(
    relationId       int(11) unsigned auto_increment
        primary key,
    slideshowImageId int          not null,
    layoutKey        varchar(255) not null
)
    engine = MyISAM
    charset = utf8mb3;

create index if not exists slideshowImageId
    on entSlideshowImageToLayout (slideshowImageId, layoutKey);

create table if not exists entState
(
    stateId      int(11) unsigned not null,
    stateTitle   varchar(255)     not null,
    stateCreated datetime         not null
)
    engine = MyISAM
    charset = utf8mb3;

create table if not exists entStateCommunal
(
    communalId      int(11) unsigned not null,
    communalStateId int              not null,
    communalTitle   varchar(255)     not null,
    communalCreated datetime         not null
)
    engine = MyISAM
    charset = utf8mb3;

create table if not exists entTinyMceTempData
(
    tempId       int unsigned auto_increment
        primary key,
    tempContent  text         not null,
    tempChkSum   varchar(255) not null,
    tempGroupKey varchar(255) not null,
    tempCreated  datetime     not null
)
    engine = MyISAM
    charset = utf8mb3;

create table if not exists entUser
(
    userId            int unsigned auto_increment
        primary key,
    username          varchar(50)                      not null,
    userPass          char(128)                        not null,
    userEmail         varchar(255)                     not null,
    userLastActive    datetime                         not null,
    userLastIp        int unsigned                     not null,
    userLastSessionId varchar(40)                      not null,
    userGrantedUsage  enum ('yes', 'no')               not null,
    userStatus        enum ('offline', 'online')       not null,
    userTermsOfUse    enum ('unconfirmed', 'accepted') not null,
    userTermsText     text                             not null,
    userCreated       datetime                         not null,
    userUpdated       datetime                         not null,
    constraint username_unique
        unique (username)
)
    engine = MyISAM
    charset = utf8mb3;

create index if not exists userName
    on entUser (username, userPass);

create table if not exists entUserGroup
(
    groupKey   varchar(255) not null
        primary key,
    groupTitle varchar(255) not null
)
    engine = MyISAM
    charset = utf8mb3;

create table if not exists entUserInfo
(
    infoUserId            int unsigned default 0 not null,
    infoCountry           int(10)                not null,
    infoUserPin           varchar(255)           not null,
    infoVatNo             varchar(255)           not null,
    infoContactPerson     varchar(255)           not null,
    infoName              varchar(255)           not null,
    infoFirstname         varchar(255)           not null,
    infoSurname           varchar(255)           not null,
    infoAddress           varchar(255)           not null,
    infoBoxAddress        varchar(255)           not null,
    infoZipCode           varchar(255)           not null,
    infoCity              varchar(255)           not null,
    infoPhone             varchar(255)           not null,
    infoDeliveryCountry   int(10)                not null,
    infoDeliveryName      varchar(255)           not null,
    infoDeliveryFirstname varchar(255)           not null,
    infoDeliverySurname   varchar(255)           not null,
    infoDeliveryAddress   varchar(255)           not null,
    infoDeliveryAddress2  varchar(255)           not null,
    infoDeliveryZipCode   varchar(255)           not null,
    infoDeliveryCity      varchar(255)           not null,
    infoDeliveryPhone     varchar(255)           not null,
    infoPaymentCountry    int(10)                not null,
    infoPaymentName       varchar(255)           not null,
    infoPaymentFirstname  varchar(255)           not null,
    infoPaymentSurname    varchar(255)           not null,
    infoPaymentAddress    varchar(255)           not null,
    infoPaymentBoxAddress varchar(255)           not null,
    infoPaymentZipCode    varchar(255)           not null,
    infoPaymentCity       varchar(255)           not null,
    infoPaymentPhone      varchar(255)           not null,
    constraint infoUserId
        unique (infoUserId)
)
    engine = MyISAM
    charset = utf8mb3;

create table if not exists entUserLog
(
    userlogId         int unsigned auto_increment
        primary key,
    username          varchar(255) not null,
    userlogParentType varchar(255) not null,
    userlogParentId   varchar(255) not null,
    userlogEvent      varchar(255) not null,
    userlogCreated    datetime     not null,
    userlogUpdated    datetime     not null
)
    engine = MyISAM
    charset = utf8mb3;

create table if not exists entUserPassRetrieval
(
    retrievalId            int unsigned auto_increment
        primary key,
    retrievalActivationKey char(128)    not null,
    retrievalPass          char(128)    not null,
    retrievalUserId        int unsigned not null,
    retrievalIp            int unsigned not null,
    retrievalCreated       datetime     not null
)
    engine = MyISAM
    charset = utf8mb3;

create index if not exists retrievalActivationKey
    on entUserPassRetrieval (retrievalActivationKey);

create table if not exists entUserToGroup
(
    userId   int unsigned not null,
    groupKey varchar(255) not null
)
    engine = MyISAM
    charset = utf8mb3;

create index if not exists userId
    on entUserToGroup (userId, groupKey);

create table if not exists entVat
(
    vatId        int unsigned auto_increment
        primary key,
    vatCountryId int unsigned not null,
    vatValue     float        not null,
    vatCreated   datetime     not null,
    constraint freightCountryId
        unique (vatCountryId)
)
    engine = MyISAM
    charset = utf8mb3;

create table if not exists entView
(
    viewId        int unsigned auto_increment
        primary key,
    viewType      enum ('html', 'xml') not null,
    viewModuleKey varchar(255)         not null,
    viewFile      varchar(255)         not null
)
    engine = MyISAM
    charset = utf8mb3;

create index if not exists viewType
    on entView (viewType);

create table if not exists entViewToSection
(
    viewId    int unsigned not null,
    sectionId int unsigned not null,
    position  smallint(5)  null
)
    engine = MyISAM
    charset = utf8mb3;

create index if not exists sectionId
    on entViewToSection (sectionId);


