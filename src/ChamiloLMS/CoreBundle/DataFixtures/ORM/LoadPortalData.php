<?php

namespace ChamiloLMS\CoreBundle\DataFixtures\ORM;

use ChamiloLMS\CoreBundle\Entity\CourseCategory;
use ChamiloLMS\CoreBundle\Entity\CourseField;
use ChamiloLMS\CoreBundle\Entity\Language;
use ChamiloLMS\CoreBundle\Entity\AccessUrl;
use ChamiloLMS\CoreBundle\Entity\AccessUrlRelUser;
use ChamiloLMS\CoreBundle\Entity\SystemTemplate;
use ChamiloLMS\CoreBundle\Entity\UserFriendRelationType;
use ChamiloLMS\CoreBundle\Entity\Skill;
use ChamiloLMS\CoreBundle\Entity\SkillRelSkill;
use ChamiloLMS\CoreBundle\Entity\CourseType;
use ChamiloLMS\CoreBundle\Entity\BranchSync;
use ChamiloLMS\CoreBundle\Entity\BranchTransactionStatus;
use ChamiloLMS\CoreBundle\Entity\Tool;

use Doctrine\Common\DataFixtures\FixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Intl\Intl;
use Symfony\Component\Finder\Finder;

/**
 * Class LoadPortalData
 * @package ChamiloLMS\CoreBundle\DataFixtures\ORM
 */
class LoadPortalData extends AbstractFixture implements ContainerAwareInterface, OrderedFixtureInterface
{
    private $container;

    /**
     * @return int
     */
    public function getOrder()
    {
        return 2;
    }

    /**
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @param ObjectManager $manager
     */
    public function load(ObjectManager $manager)
    {
        /** @var HttpKernelInterface $kernel */
        $kernel = $this->container->get('kernel');

        $courseCategory = new CourseCategory();
        $courseCategory->setName('Language skills');
        $courseCategory->setCode('LANG');
        $courseCategory->setTreePos(1);
        $courseCategory->setAuthCatChild('TRUE');
        $courseCategory->setAuthCourseChild('TRUE');
        $manager->persist($courseCategory);

        $courseCategory = new CourseCategory();
        $courseCategory->setName('PC Skills');
        $courseCategory->setCode('PC');
        $courseCategory->setTreePos(2);
        $courseCategory->setAuthCatChild('TRUE');
        $courseCategory->setAuthCourseChild('TRUE');
        $manager->persist($courseCategory);

        $courseCategory = new CourseCategory();
        $courseCategory->setName('Projects');
        $courseCategory->setCode('PROJ');
        $courseCategory->setTreePos(3);
        $courseCategory->setAuthCatChild('TRUE');
        $courseCategory->setAuthCourseChild('TRUE');
        $manager->persist($courseCategory);

        $courseField = new CourseField();
        $courseField->setFieldType(13);
        $courseField->setFieldVariable('special_course');
        $courseField->setFieldDisplayText('Special course');
        $courseField->setFieldDefaultValue('Yes');
        $courseField->setFieldVisible(1);
        $courseField->setFieldChangeable(1);
        $manager->persist($courseField);

        /*
            Saving available languages depending in
            the ChamiloLMSCoreBundle/Resources/translations folder
        */
        $languages = Intl::getLocaleBundle()->getLocaleNames('en');

        // Getting po files inside the path
        $translationPath = $kernel->locateResource('@ChamiloLMSCoreBundle/Resources/translations');

        $finder = new Finder();
        $finder->files()->in($translationPath);
        $avoidIsoCodeList = array('AvanzuAdminTheme.en.po');
        $availableIsoCode = array();
        foreach ($finder as $file) {
            $fileName = $file->getRelativePathname();
            if (in_array($fileName, $avoidIsoCodeList)) {
                continue;
            }
            $isoCodeInFolder = str_replace(
                array('all.', '.po'), '', $fileName
            );
            $availableIsoCode[] = $isoCodeInFolder;
        }

        foreach ($languages as $code => $languageName) {
            if (!in_array($code, $availableIsoCode)) {
                continue;
            }

            \Locale::setDefault($code);
            $localeName = Intl::getLocaleBundle()->getLocaleName($code);

            $lang = new Language();
            $lang->setAvailable(1);
            $lang->setIsocode($code);
            $lang->setOriginalName($localeName);
            $lang->setEnglishName($languageName);
            $manager->persist($lang);
        }

        $adminUserId = $this->getReference('admin-user')->getId();

        $accessUrl = new AccessUrl();
        $accessUrl->setUrl('http://localhost/');
        $accessUrl->setActive(1);
        $accessUrl->setDescription(' ');
        $accessUrl->setCreatedBy($adminUserId);
        $manager->persist($accessUrl);

        $accessUrlRelUser = new AccessUrlRelUser();
        $accessUrlRelUser->setUserId($adminUserId);
        $accessUrlRelUser->setAccessUrlId($accessUrl->getId());
        $manager->persist($accessUrlRelUser);

        /*$systemTemplate = new SystemTemplate();
        $systemTemplate->setTitle('');
        $systemTemplate->setComment('');
        $systemTemplate->setImage('');
        $systemTemplate->setContent('');*/

        $userFriendRelationType = new UserFriendRelationType();
        $userFriendRelationType->setId(1);
        $userFriendRelationType->setTitle('SocialUnknow');
        $manager->persist($userFriendRelationType);

        $userFriendRelationType = new UserFriendRelationType();
        $userFriendRelationType->setId(2);
        $userFriendRelationType->setTitle('SocialParent');
        $manager->persist($userFriendRelationType);

        $userFriendRelationType = new UserFriendRelationType();
        $userFriendRelationType->setId(3);
        $userFriendRelationType->setTitle('SocialFriend');
        $manager->persist($userFriendRelationType);

        $userFriendRelationType = new UserFriendRelationType();
        $userFriendRelationType->setId(4);
        $userFriendRelationType->setTitle('SocialGoodFriend');
        $manager->persist($userFriendRelationType);

        $userFriendRelationType = new UserFriendRelationType();
        $userFriendRelationType->setId(5);
        $userFriendRelationType->setTitle('SocialEnemy');
        $manager->persist($userFriendRelationType);

        $userFriendRelationType = new UserFriendRelationType();
        $userFriendRelationType->setId(6);
        $userFriendRelationType->setTitle('SocialDeleted');
        $manager->persist($userFriendRelationType);

        $skill = new Skill();
        $skill->setName('Root');
        $manager->persist($skill);

        $skillRelSkill = new SkillRelSkill();
        $skillRelSkill->setId(1);
        $skillRelSkill->setSkillId(1);
        $skillRelSkill->setParentId(0);
        $skillRelSkill->setRelationType(0);
        $skillRelSkill->setLevel(0);
        $manager->persist($skillRelSkill);

        $courseType = new CourseType();
        $courseType->setName('All Tools');
        $manager->persist($courseType);

        $courseType = new CourseType();
        $courseType->setName('Entry exam');
        $manager->persist($courseType);

        return;

        $branch = new BranchSync();
        $branch->setAccessUrlId(1);
        $branch->setBranchName('Local');
        $branch->setBranchIp('127.0.0.1');
        $manager->persist($branch);

        $branchTransactionStatus = new BranchTransactionStatus();
        $branchTransactionStatus->setTitle('To be executed');
        $manager->persist($branchTransactionStatus);

        $branchTransactionStatus = new BranchTransactionStatus();
        $branchTransactionStatus->setTitle('Executed successfully');
        $manager->persist($branchTransactionStatus);

        $branchTransactionStatus = new BranchTransactionStatus();
        $branchTransactionStatus->setTitle('Execution deprecated');
        $manager->persist($branchTransactionStatus);

        $branchTransactionStatus = new BranchTransactionStatus();
        $branchTransactionStatus->setTitle('Execution failed');
        $manager->persist($branchTransactionStatus);

        $tool = new Tool();
        $tool->setName('agenda');
        $manager->persist($tool);

        $tool = new Tool();
        $tool->setName('announcements');
        $manager->persist($tool);

        $tool = new Tool();
        $tool->setName('exercise');
        $manager->persist($tool);

        $tool = new Tool();
        $tool->setName('document');
        $manager->persist($tool);

        $tool = new Tool();
        $tool->setName('link');
        $manager->persist($tool);

        $tool = new Tool();
        $tool->setName('forum');
        $manager->persist($tool);

        $tool = new Tool();
        $tool->setName('glossary');
        $manager->persist($tool);

        $manager->flush();
    }

    /**
     * @return \FOS\UserBundle\Model\UserManagerInterface
     */
    public function getManager()
    {
        return $this->container->get('doctrine')->getManager();
    }

    /**
     * @return \Faker\Generator
     */
    public function getFaker()
    {
        return $this->container->get('faker.generator');
    }
}
