<?php

declare(strict_types=1);

namespace Stu\Orm\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Stu\Orm\Entity\Alliance;
use Stu\Orm\Entity\AllianceBoard;
use Stu\Orm\Entity\AllianceBoardPost;
use Stu\Orm\Entity\AllianceBoardTopic;
use Stu\Orm\Entity\AllianceJob;
use Stu\Orm\Entity\AllianceRelation;
use Stu\Orm\Entity\Anomaly;
use Stu\Orm\Entity\AnomalyType;
use Stu\Orm\Entity\AstronomicalEntry;
use Stu\Orm\Entity\AuctionBid;
use Stu\Orm\Entity\Award;
use Stu\Orm\Entity\BasicTrade;
use Stu\Orm\Entity\BlockedUser;
use Stu\Orm\Entity\Building;
use Stu\Orm\Entity\BuildingCommodity;
use Stu\Orm\Entity\BuildingCost;
use Stu\Orm\Entity\BuildingFieldAlternative;
use Stu\Orm\Entity\BuildingFunction;
use Stu\Orm\Entity\BuildingUpgrade;
use Stu\Orm\Entity\BuildingUpgradeCost;
use Stu\Orm\Entity\BuildplanHangar;
use Stu\Orm\Entity\BuildplanModule;
use Stu\Orm\Entity\Buoy;
use Stu\Orm\Entity\Colony;
use Stu\Orm\Entity\ColonyClass;
use Stu\Orm\Entity\ColonyClassDeposit;
use Stu\Orm\Entity\ColonyClassResearch;
use Stu\Orm\Entity\ColonyDepositMining;
use Stu\Orm\Entity\ColonySandbox;
use Stu\Orm\Entity\ColonyScan;
use Stu\Orm\Entity\ColonyShipQueue;
use Stu\Orm\Entity\ColonyShipRepair;
use Stu\Orm\Entity\ColonyTerraforming;
use Stu\Orm\Entity\Commodity;
use Stu\Orm\Entity\ConstructionProgress;
use Stu\Orm\Entity\ConstructionProgressModule;
use Stu\Orm\Entity\Contact;
use Stu\Orm\Entity\Crew;
use Stu\Orm\Entity\CrewRace;
use Stu\Orm\Entity\CrewTraining;
use Stu\Orm\Entity\DatabaseCategory;
use Stu\Orm\Entity\DatabaseEntry;
use Stu\Orm\Entity\DatabaseType;
use Stu\Orm\Entity\DatabaseUser;
use Stu\Orm\Entity\Deals;
use Stu\Orm\Entity\DockingPrivilege;
use Stu\Orm\Entity\Faction;
use Stu\Orm\Entity\Fleet;
use Stu\Orm\Entity\FlightSignature;
use Stu\Orm\Entity\GameConfig;
use Stu\Orm\Entity\GameRequest;
use Stu\Orm\Entity\GameTurn;
use Stu\Orm\Entity\GameTurnStats;
use Stu\Orm\Entity\History;
use Stu\Orm\Entity\IgnoreList;
use Stu\Orm\Entity\KnCharacters;
use Stu\Orm\Entity\KnComment;
use Stu\Orm\Entity\KnPost;
use Stu\Orm\Entity\KnPostToPlotApplication;
use Stu\Orm\Entity\Layer;
use Stu\Orm\Entity\Location;
use Stu\Orm\Entity\LocationMining;
use Stu\Orm\Entity\LotteryTicket;
use Stu\Orm\Entity\Map;
use Stu\Orm\Entity\MapBorderType;
use Stu\Orm\Entity\MapFieldType;
use Stu\Orm\Entity\MapRegion;
use Stu\Orm\Entity\MiningQueue;
use Stu\Orm\Entity\Module;
use Stu\Orm\Entity\ModuleBuildingFunction;
use Stu\Orm\Entity\ModuleCost;
use Stu\Orm\Entity\ModuleQueue;
use Stu\Orm\Entity\ModuleSpecial;
use Stu\Orm\Entity\Names;
use Stu\Orm\Entity\News;
use Stu\Orm\Entity\Note;
use Stu\Orm\Entity\NPCLog;
use Stu\Orm\Entity\OpenedAdventDoor;
use Stu\Orm\Entity\PartnerSite;
use Stu\Orm\Entity\PirateSetup;
use Stu\Orm\Entity\PirateWrath;
use Stu\Orm\Entity\PlanetField;
use Stu\Orm\Entity\PlanetFieldType;
use Stu\Orm\Entity\PlanetFieldTypeBuilding;
use Stu\Orm\Entity\PrestigeLog;
use Stu\Orm\Entity\PrivateMessage;
use Stu\Orm\Entity\PrivateMessageFolder;
use Stu\Orm\Entity\RepairTask;
use Stu\Orm\Entity\Research;
use Stu\Orm\Entity\ResearchDependency;
use Stu\Orm\Entity\Researched;
use Stu\Orm\Entity\RpgPlot;
use Stu\Orm\Entity\RpgPlotMember;
use Stu\Orm\Entity\SessionString;
use Stu\Orm\Entity\Ship;
use Stu\Orm\Entity\ShipBuildplan;
use Stu\Orm\Entity\ShipCrew;
use Stu\Orm\Entity\ShipLog;
use Stu\Orm\Entity\ShipRump;
use Stu\Orm\Entity\ShipRumpBuildingFunction;
use Stu\Orm\Entity\ShipRumpCategory;
use Stu\Orm\Entity\ShipRumpCategoryRoleCrew;
use Stu\Orm\Entity\ShipRumpColonizationBuilding;
use Stu\Orm\Entity\ShipRumpCost;
use Stu\Orm\Entity\ShipRumpModuleLevel;
use Stu\Orm\Entity\ShipRumpRole;
use Stu\Orm\Entity\ShipRumpSpecial;
use Stu\Orm\Entity\ShipRumpUser;
use Stu\Orm\Entity\ShipSystem;
use Stu\Orm\Entity\ShipTakeover;
use Stu\Orm\Entity\ShipyardShipQueue;
use Stu\Orm\Entity\SpacecraftEmergency;
use Stu\Orm\Entity\StarSystem;
use Stu\Orm\Entity\StarSystemMap;
use Stu\Orm\Entity\StarSystemType;
use Stu\Orm\Entity\StationShipRepair;
use Stu\Orm\Entity\Storage;
use Stu\Orm\Entity\TachyonScan;
use Stu\Orm\Entity\Terraforming;
use Stu\Orm\Entity\TerraformingCost;
use Stu\Orm\Entity\TholianWeb;
use Stu\Orm\Entity\TorpedoHull;
use Stu\Orm\Entity\TorpedoStorage;
use Stu\Orm\Entity\TorpedoType;
use Stu\Orm\Entity\TradeLicense;
use Stu\Orm\Entity\TradeLicenseInfo;
use Stu\Orm\Entity\TradeOffer;
use Stu\Orm\Entity\TradePost;
use Stu\Orm\Entity\TradeShoutbox;
use Stu\Orm\Entity\TradeTransaction;
use Stu\Orm\Entity\TradeTransfer;
use Stu\Orm\Entity\User;
use Stu\Orm\Entity\UserAward;
use Stu\Orm\Entity\UserCharacters;
use Stu\Orm\Entity\UserInvitation;
use Stu\Orm\Entity\UserIpTable;
use Stu\Orm\Entity\UserLayer;
use Stu\Orm\Entity\UserLock;
use Stu\Orm\Entity\UserMap;
use Stu\Orm\Entity\UserProfileVisitor;
use Stu\Orm\Entity\UserSetting;
use Stu\Orm\Entity\UserTag;
use Stu\Orm\Entity\Weapon;
use Stu\Orm\Entity\WeaponShield;
use Stu\Orm\Entity\WormholeEntry;

return [
    AllianceRepositoryInterface::class => fn(ContainerInterface $c): AllianceRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(Alliance::class),
    AllianceBoardRepositoryInterface::class => fn(ContainerInterface $c): AllianceBoardRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(AllianceBoard::class),
    AllianceBoardPostRepositoryInterface::class => fn(ContainerInterface $c): AllianceBoardPostRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(AllianceBoardPost::class),
    AllianceBoardTopicRepositoryInterface::class => fn(ContainerInterface $c): AllianceBoardTopicRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(AllianceBoardTopic::class),
    AllianceJobRepositoryInterface::class => fn(ContainerInterface $c): AllianceJobRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(AllianceJob::class),
    AllianceRelationRepositoryInterface::class => fn(ContainerInterface $c): AllianceRelationRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(AllianceRelation::class),
    AnomalyTypeRepositoryInterface::class => fn(ContainerInterface $c): AnomalyTypeRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(AnomalyType::class),
    AnomalyRepositoryInterface::class => fn(ContainerInterface $c): AnomalyRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(Anomaly::class),
    AwardRepositoryInterface::class => fn(ContainerInterface $c): AwardRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(Award::class),
    BasicTradeRepositoryInterface::class => fn(ContainerInterface $c): BasicTradeRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(BasicTrade::class),
    BlockedUserRepositoryInterface::class => fn(ContainerInterface $c): BlockedUserRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(BlockedUser::class),
    BuildingRepositoryInterface::class => fn(ContainerInterface $c): BuildingRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(Building::class),
    BuildingCostRepositoryInterface::class => fn(ContainerInterface $c): BuildingCostRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(BuildingCost::class),
    BuildingFieldAlternativeRepositoryInterface::class => fn(ContainerInterface $c): BuildingFieldAlternativeRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(BuildingFieldAlternative::class),
    BuildingFunctionRepositoryInterface::class => fn(ContainerInterface $c): BuildingFunctionRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(BuildingFunction::class),
    BuildingCommodityRepositoryInterface::class => fn(ContainerInterface $c): BuildingCommodityRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(BuildingCommodity::class),
    BuildingUpgradeRepositoryInterface::class => fn(ContainerInterface $c): BuildingUpgradeRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(BuildingUpgrade::class),
    BuildingUpgradeCostRepositoryInterface::class => fn(ContainerInterface $c): BuildingUpgradeCostRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(BuildingUpgradeCost::class),
    BuildplanHangarRepositoryInterface::class => fn(ContainerInterface $c): BuildplanHangarRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(BuildplanHangar::class),
    BuildplanModuleRepositoryInterface::class => fn(ContainerInterface $c): BuildplanModuleRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(BuildplanModule::class),
    BuoyRepositoryInterface::class => fn(ContainerInterface $c): BuoyRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(Buoy::class),
    ColonyRepositoryInterface::class => fn(ContainerInterface $c): ColonyRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(Colony::class),
    ColonySandboxRepositoryInterface::class => fn(ContainerInterface $c): ColonySandboxRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(ColonySandbox::class),
    ColonyTerraformingRepositoryInterface::class => fn(ContainerInterface $c): ColonyTerraformingRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(ColonyTerraforming::class),
    CommodityRepositoryInterface::class => fn(ContainerInterface $c): CommodityRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(Commodity::class),
    ConstructionProgressRepositoryInterface::class => fn(ContainerInterface $c): ConstructionProgressRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(ConstructionProgress::class),
    ConstructionProgressModuleRepositoryInterface::class => fn(ContainerInterface $c): ConstructionProgressModuleRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(ConstructionProgressModule::class),
    ContactRepositoryInterface::class => fn(ContainerInterface $c): ContactRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(Contact::class),
    ColonyClassDepositRepositoryInterface::class => fn(ContainerInterface $c): ColonyClassDepositRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(ColonyClassDeposit::class),
    ColonyDepositMiningRepositoryInterface::class => fn(ContainerInterface $c): ColonyDepositMiningRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(ColonyDepositMining::class),
    ColonyShipRepairRepositoryInterface::class => fn(ContainerInterface $c): ColonyShipRepairRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(ColonyShipRepair::class),
    ColonyShipQueueRepositoryInterface::class => fn(ContainerInterface $c): ColonyShipQueueRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(ColonyShipQueue::class),
    ColonyScanRepositoryInterface::class => fn(ContainerInterface $c): ColonyScanRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(ColonyScan::class),
    CrewRaceRepositoryInterface::class => fn(ContainerInterface $c): CrewRaceRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(CrewRace::class),
    CrewRepositoryInterface::class => fn(ContainerInterface $c): CrewRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(Crew::class),
    CrewTrainingRepositoryInterface::class => fn(ContainerInterface $c): CrewTrainingRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(CrewTraining::class),
    DatabaseCategoryRepositoryInterface::class => fn(ContainerInterface $c): DatabaseCategoryRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(DatabaseCategory::class),
    DatabaseEntryRepositoryInterface::class => fn(ContainerInterface $c): DatabaseEntryRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(DatabaseEntry::class),
    DatabaseTypeRepositoryInterface::class => fn(ContainerInterface $c): DatabaseTypeRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(DatabaseType::class),
    DatabaseUserRepositoryInterface::class => fn(ContainerInterface $c): DatabaseUserRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(DatabaseUser::class),
    DealsRepositoryInterface::class => fn(ContainerInterface $c): DealsRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(Deals::class),
    AuctionBidRepositoryInterface::class => fn(ContainerInterface $c): AuctionBidRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(AuctionBid::class),
    DockingPrivilegeRepositoryInterface::class => fn(ContainerInterface $c): DockingPrivilegeRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(DockingPrivilege::class),
    FactionRepositoryInterface::class => fn(ContainerInterface $c): FactionRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(Faction::class),
    FleetRepositoryInterface::class => fn(ContainerInterface $c): FleetRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(Fleet::class),
    FlightSignatureRepositoryInterface::class => fn(ContainerInterface $c): FlightSignatureRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(FlightSignature::class),
    AstroEntryRepositoryInterface::class => fn(ContainerInterface $c): AstroEntryRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(AstronomicalEntry::class),
    GameConfigRepositoryInterface::class => fn(ContainerInterface $c): GameConfigRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(GameConfig::class),
    GameTurnRepositoryInterface::class => fn(ContainerInterface $c): GameTurnRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(GameTurn::class),
    GameRequestRepositoryInterface::class => fn(ContainerInterface $c): GameRequestRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(GameRequest::class),
    GameTurnStatsRepositoryInterface::class => fn(ContainerInterface $c): GameTurnStatsRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(GameTurnStats::class),
    HistoryRepositoryInterface::class => fn(ContainerInterface $c): HistoryRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(History::class),
    IgnoreListRepositoryInterface::class => fn(ContainerInterface $c): IgnoreListRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(IgnoreList::class),
    KnCharactersRepositoryInterface::class => fn(ContainerInterface $c): KnCharactersRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(KnCharacters::class),
    KnCommentRepositoryInterface::class => fn(ContainerInterface $c): KnCommentRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(KnComment::class),
    KnPostRepositoryInterface::class => fn(ContainerInterface $c): KnPostRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(KnPost::class),
    KnPostToPlotApplicationRepositoryInterface::class => fn(ContainerInterface $c): KnPostToPlotApplicationRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(KnPostToPlotApplication::class),
    LayerRepositoryInterface::class => fn(ContainerInterface $c): LayerRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(Layer::class),
    LocationRepositoryInterface::class => fn(ContainerInterface $c): LocationRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(Location::class),
    LocationMiningRepositoryInterface::class => fn(ContainerInterface $c): LocationMiningRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(LocationMining::class),
    LotteryTicketRepositoryInterface::class => fn(ContainerInterface $c): LotteryTicketRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(LotteryTicket::class),
    MapBorderTypeRepositoryInterface::class => fn(ContainerInterface $c): MapBorderTypeRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(MapBorderType::class),
    MapFieldTypeRepositoryInterface::class => fn(ContainerInterface $c): MapFieldTypeRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(MapFieldType::class),
    MapRegionRepositoryInterface::class => fn(ContainerInterface $c): MapRegionRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(MapRegion::class),
    MapRepositoryInterface::class => fn(ContainerInterface $c): MapRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(Map::class),
    MiningQueueRepositoryInterface::class => fn(ContainerInterface $c): MiningQueueRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(MiningQueue::class),
    ModuleBuildingFunctionRepositoryInterface::class => fn(ContainerInterface $c): ModuleBuildingFunctionRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(ModuleBuildingFunction::class),
    ModuleCostRepositoryInterface::class => fn(ContainerInterface $c): ModuleCostRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(ModuleCost::class),
    ModuleRepositoryInterface::class => fn(ContainerInterface $c): ModuleRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(Module::class),
    ModuleQueueRepositoryInterface::class => fn(ContainerInterface $c): ModuleQueueRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(ModuleQueue::class),
    ModuleSpecialRepositoryInterface::class => fn(ContainerInterface $c): ModuleSpecialRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(ModuleSpecial::class),
    NewsRepositoryInterface::class => fn(ContainerInterface $c): NewsRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(News::class),
    NoteRepositoryInterface::class => fn(ContainerInterface $c): NoteRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(Note::class),
    NPCLogRepositoryInterface::class => fn(ContainerInterface $c): NPCLogRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(NPCLog::class),
    OpenedAdventDoorRepositoryInterface::class => fn(ContainerInterface $c): OpenedAdventDoorRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(OpenedAdventDoor::class),
    NamesRepositoryInterface::class => fn(ContainerInterface $c): NamesRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(Names::class),
    PartnerSiteRepositoryInterface::class => fn(ContainerInterface $c): PartnerSiteRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(PartnerSite::class),
    PlanetFieldRepositoryInterface::class => fn(ContainerInterface $c): PlanetFieldRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(PlanetField::class),
    PlanetFieldTypeBuildingRepositoryInterface::class => fn(ContainerInterface $c): PlanetFieldTypeBuildingRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(PlanetFieldTypeBuilding::class),
    PlanetFieldTypeRepositoryInterface::class => fn(ContainerInterface $c): PlanetFieldTypeRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(PlanetFieldType::class),
    ColonyClassRepositoryInterface::class => fn(ContainerInterface $c): ColonyClassRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(ColonyClass::class),
    ColonyClassResearchRepositoryInterface::class => fn(ContainerInterface $c): ColonyClassResearchRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(ColonyClassResearch::class),
    PirateSetupRepositoryInterface::class => fn(ContainerInterface $c): PirateSetupRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(PirateSetup::class),
    PirateWrathRepositoryInterface::class => fn(ContainerInterface $c): PirateWrathRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(PirateWrath::class),
    PrestigeLogRepositoryInterface::class => fn(ContainerInterface $c): PrestigeLogRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(PrestigeLog::class),
    PrivateMessageRepositoryInterface::class => fn(ContainerInterface $c): PrivateMessageRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(PrivateMessage::class),
    PrivateMessageFolderRepositoryInterface::class => fn(ContainerInterface $c): PrivateMessageFolderRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(PrivateMessageFolder::class),
    RepairTaskRepositoryInterface::class => fn(ContainerInterface $c): RepairTaskRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(RepairTask::class),
    ResearchRepositoryInterface::class => fn(ContainerInterface $c): ResearchRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(Research::class),
    ResearchedRepositoryInterface::class => fn(ContainerInterface $c): ResearchedRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(Researched::class),
    ResearchDependencyRepositoryInterface::class => fn(ContainerInterface $c): ResearchDependencyRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(ResearchDependency::class),
    RpgPlotRepositoryInterface::class => fn(ContainerInterface $c): RpgPlotRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(RpgPlot::class),
    RpgPlotMemberRepositoryInterface::class => fn(ContainerInterface $c): RpgPlotMemberRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(RpgPlotMember::class),
    SessionStringRepositoryInterface::class => fn(ContainerInterface $c): SessionStringRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(SessionString::class),
    ShipBuildplanRepositoryInterface::class => fn(ContainerInterface $c): ShipBuildplanRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(ShipBuildplan::class),
    ShipCrewRepositoryInterface::class => fn(ContainerInterface $c): ShipCrewRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(ShipCrew::class),
    ShipLogRepositoryInterface::class => fn(ContainerInterface $c): ShipLogRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(ShipLog::class),
    ShipRepositoryInterface::class => fn(ContainerInterface $c): ShipRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(Ship::class),
    ShipRumpBuildingFunctionRepositoryInterface::class => fn(ContainerInterface $c): ShipRumpBuildingFunctionRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(ShipRumpBuildingFunction::class),
    ShipRumpCategoryRepositoryInterface::class => fn(ContainerInterface $c): ShipRumpCategoryRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(ShipRumpCategory::class),
    ShipRumpCategoryRoleCrewRepositoryInterface::class => fn(ContainerInterface $c): ShipRumpCategoryRoleCrewRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(ShipRumpCategoryRoleCrew::class),
    ShipRumpColonizationBuildingRepositoryInterface::class => fn(ContainerInterface $c): ShipRumpColonizationBuildingRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(ShipRumpColonizationBuilding::class),
    ShipRumpCostRepositoryInterface::class => fn(ContainerInterface $c): ShipRumpCostRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(ShipRumpCost::class),
    ShipRumpModuleLevelRepositoryInterface::class => fn(ContainerInterface $c): ShipRumpModuleLevelRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(ShipRumpModuleLevel::class),
    ShipRumpRepositoryInterface::class => fn(ContainerInterface $c): ShipRumpRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(ShipRump::class),
    ShipRumpRoleRepositoryInterface::class => fn(ContainerInterface $c): ShipRumpRoleRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(ShipRumpRole::class),
    ShipRumpSpecialRepositoryInterface::class => fn(ContainerInterface $c): ShipRumpSpecialRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(ShipRumpSpecial::class),
    ShipRumpUserRepositoryInterface::class => fn(ContainerInterface $c): ShipRumpUserRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(ShipRumpUser::class),
    ShipSystemRepositoryInterface::class => fn(ContainerInterface $c): ShipSystemRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(ShipSystem::class),
    ShipTakeoverRepositoryInterface::class => fn(ContainerInterface $c): ShipTakeoverRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(ShipTakeover::class),
    ShipyardShipQueueRepositoryInterface::class => fn(ContainerInterface $c): ShipyardShipQueueRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(ShipyardShipQueue::class),
    SpacecraftEmergencyRepositoryInterface::class => fn(ContainerInterface $c): SpacecraftEmergencyRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(SpacecraftEmergency::class),
    StarSystemMapRepositoryInterface::class => fn(ContainerInterface $c): StarSystemMapRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(StarSystemMap::class),
    StarSystemRepositoryInterface::class => fn(ContainerInterface $c): StarSystemRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(StarSystem::class),
    StarSystemTypeRepositoryInterface::class => fn(ContainerInterface $c): StarSystemTypeRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(StarSystemType::class),
    StationShipRepairRepositoryInterface::class => fn(ContainerInterface $c): StationShipRepairRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(StationShipRepair::class),
    StorageRepositoryInterface::class => fn(ContainerInterface $c): StorageRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(Storage::class),
    TerraformingRepositoryInterface::class => fn(ContainerInterface $c): TerraformingRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(Terraforming::class),
    TachyonScanRepositoryInterface::class => fn(ContainerInterface $c): TachyonScanRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(TachyonScan::class),
    TerraformingCostRepositoryInterface::class => fn(ContainerInterface $c): TerraformingCostRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(TerraformingCost::class),
    TholianWebRepositoryInterface::class => fn(ContainerInterface $c): TholianWebRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(TholianWeb::class),
    TorpedoHullRepositoryInterface::class => fn(ContainerInterface $c): TorpedoHullRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(TorpedoHull::class),
    TorpedoTypeRepositoryInterface::class => fn(ContainerInterface $c): TorpedoTypeRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(TorpedoType::class),
    TorpedoStorageRepositoryInterface::class => fn(ContainerInterface $c): TorpedoStorageRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(TorpedoStorage::class),
    TradeLicenseInfoRepositoryInterface::class => fn(ContainerInterface $c): TradeLicenseInfoRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(TradeLicenseInfo::class),
    TradeLicenseRepositoryInterface::class => fn(ContainerInterface $c): TradeLicenseRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(TradeLicense::class),
    TradeOfferRepositoryInterface::class => fn(ContainerInterface $c): TradeOfferRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(TradeOffer::class),
    TradePostRepositoryInterface::class => fn(ContainerInterface $c): TradePostRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(TradePost::class),
    TradeShoutboxRepositoryInterface::class => fn(ContainerInterface $c): TradeShoutboxRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(TradeShoutbox::class),
    TradeTransactionRepositoryInterface::class => fn(ContainerInterface $c): TradeTransactionRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(TradeTransaction::class),
    TradeTransferRepositoryInterface::class => fn(ContainerInterface $c): TradeTransferRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(TradeTransfer::class),
    UserAwardRepositoryInterface::class => fn(ContainerInterface $c): UserAwardRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(UserAward::class),
    UserCharactersRepositoryInterface::class => fn(ContainerInterface $c): UserCharactersRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(UserCharacters::class),
    UserLayerRepositoryInterface::class => fn(ContainerInterface $c): UserLayerRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(UserLayer::class),
    UserLockRepositoryInterface::class => fn(ContainerInterface $c): UserLockRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(UserLock::class),
    UserRepositoryInterface::class => fn(ContainerInterface $c): UserRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(User::class),
    UserIpTableRepositoryInterface::class => fn(ContainerInterface $c): UserIpTableRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(UserIpTable::class),
    UserInvitationRepositoryInterface::class => fn(ContainerInterface $c): UserInvitationRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(UserInvitation::class),
    UserMapRepositoryInterface::class => fn(ContainerInterface $c): UserMapRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(UserMap::class),
    UserProfileVisitorRepositoryInterface::class => fn(ContainerInterface $c): UserProfileVisitorRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(UserProfileVisitor::class),
    UserSettingRepositoryInterface::class => fn(ContainerInterface $c): UserSettingRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(UserSetting::class),
    UserTagRepositoryInterface::class => fn(ContainerInterface $c): UserTagRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(UserTag::class),
    WeaponRepositoryInterface::class => fn(ContainerInterface $c): WeaponRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(Weapon::class),
    WeaponShieldRepositoryInterface::class => fn(ContainerInterface $c): WeaponShieldRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(WeaponShield::class),
    WormholeEntryRepositoryInterface::class => fn(ContainerInterface $c): WormholeEntryRepositoryInterface => $c->get(EntityManagerInterface::class)->getRepository(WormholeEntry::class),
];