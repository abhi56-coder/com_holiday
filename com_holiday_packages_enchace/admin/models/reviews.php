<?php
/**
 * Holiday Packages Enhanced Component
 * 
 * @package     HolidayPackages
 * @subpackage  Administrator
 * @author      Your Name
 * @copyright   Copyright (C) 2024. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Language\Text;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\Database\ParameterType;

/**
 * Admin Reviews List Model for Holiday Packages Enhanced Component
 * 
 * Manages customer reviews and ratings for packages, including
 * moderation, analytics, and response management.
 */
class HolidayPackagesModelReviews extends ListModel
{
    /**
     * Constructor
     *
     * @param   array  $config  Configuration array
     */
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 'r.id',
                'package_title', 'p.title',
                'customer_name',
                'rating', 'r.rating',
                'published', 'r.published',
                'verified', 'r.verified',
                'featured', 'r.featured',
                'created', 'r.created',
                'package_id', 'r.package_id',
                'search'
            ];
        }

        parent::__construct($config);
    }

    /**
     * Method to auto-populate the model state
     *
     * @param   string  $ordering   Column to order by
     * @param   string  $direction  Direction to order by
     * @return  void
     */
    protected function populateState($ordering = 'r.id', $direction = 'desc')
    {
        // Load the filter search
        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search', '', 'string');
        $this->setState('filter.search', $search);

        // Load filter by published status
        $published = $this->getUserStateFromRequest($this->context . '.filter.published', 'filter_published', '', 'string');
        $this->setState('filter.published', $published);

        // Load filter by verified status
        $verified = $this->getUserStateFromRequest($this->context . '.filter.verified', 'filter_verified', '', 'string');
        $this->setState('filter.verified', $verified);

        // Load filter by featured status
        $featured = $this->getUserStateFromRequest($this->context . '.filter.featured', 'filter_featured', '', 'string');
        $this->setState('filter.featured', $featured);

        // Load filter by rating
        $rating = $this->getUserStateFromRequest($this->context . '.filter.rating', 'filter_rating', '', 'int');
        $this->setState('filter.rating', $rating);

        // Load filter by package
        $packageId = $this->getUserStateFromRequest($this->context . '.filter.package_id', 'filter_package_id', '', 'int');
        $this->setState('filter.package_id', $packageId);

        // Load filter by date range
        $dateFrom = $this->getUserStateFromRequest($this->context . '.filter.date_from', 'filter_date_from', '', 'string');
        $this->setState('filter.date_from', $dateFrom);

        $dateTo = $this->getUserStateFromRequest($this->context . '.filter.date_to', 'filter_date_to', '', 'string');
        $this->setState('filter.date_to', $dateTo);

        // Load the default list behavior
        parent::populateState($ordering, $direction);
    }

    /**
     * Method to get a store id based on model configuration state
     *
     * @param   string  $id  A prefix for the store id
     * @return  string  A store id
     */
    protected function getStoreId($id = '')
    {
        $id .= ':' . $this->getState('filter.search');
        $id .= ':' . $this->getState('filter.published');
        $id .= ':' . $this->getState('filter.verified');
        $id .= ':' . $this->getState('filter.featured');
        $id .= ':' . $this->getState('filter.rating');
        $id .= ':' . $this->getState('filter.package_id');
        $id .= ':' . $this->getState('filter.date_from');
        $id .= ':' . $this->getState('filter.date_to');

        return parent::getStoreId($id);
    }

    /**
     * Build an SQL query to load the list data
     *
     * @return  DatabaseQuery
     */
    protected function getListQuery()
    {
        $db = $this->getDatabase();
        $query = $db->getQuery(true);

        // Select review fields with related data
        $query->select([
            'r.*',
            'p.title as package_title',
            'p.destination',
            'CONCAT(c.first_name, " ", c.last_name) as customer_name',
            'c.email as customer_email',
            'b.booking_reference',
            'b.start_date as travel_date',
            'COUNT(rr.id) as response_count'
        ]);

        $query->from($db->quoteName('#__hp_reviews', 'r'));
        
        // Join with packages
        $query->leftJoin($db->quoteName('#__hp_packages', 'p') . ' ON r.package_id = p.id');
        
        // Join with customers
        $query->leftJoin($db->quoteName('#__hp_customers', 'c') . ' ON r.customer_id = c.id');
        
        // Join with bookings for verification
        $query->leftJoin($db->quoteName('#__hp_bookings', 'b') . ' ON r.booking_id = b.id');
        
        // Join with review responses
        $query->leftJoin($db->quoteName('#__hp_review_responses', 'rr') . ' ON r.id = rr.review_id');

        // Filter by search term
        $search = $this->getState('filter.search');
        if (!empty($search)) {
            if (stripos($search, 'id:') === 0) {
                $query->where($db->quoteName('r.id') . ' = ' . (int) substr($search, 3));
            } else {
                $search = $db->quote('%' . str_replace(' ', '%', $db->escape(trim($search), true) . '%'));
                $query->where(
                    '(' . $db->quoteName('r.title') . ' LIKE ' . $search .
                    ' OR ' . $db->quoteName('r.comment') . ' LIKE ' . $search .
                    ' OR ' . $db->quoteName('p.title') . ' LIKE ' . $search .
                    ' OR ' . $db->quoteName('c.first_name') . ' LIKE ' . $search .
                    ' OR ' . $db->quoteName('c.last_name') . ' LIKE ' . $search .
                    ' OR CONCAT(' . $db->quoteName('c.first_name') . ', " ", ' . $db->quoteName('c.last_name') . ') LIKE ' . $search . ')'
                );
            }
        }

        // Filter by published status
        $published = $this->getState('filter.published');
        if (is_numeric($published)) {
            $query->where($db->quoteName('r.published') . ' = :published')
                  ->bind(':published', $published, ParameterType::INTEGER);
        }

        // Filter by verified status
        $verified = $this->getState('filter.verified');
        if (is_numeric($verified)) {
            $query->where($db->quoteName('r.verified') . ' = :verified')
                  ->bind(':verified', $verified, ParameterType::INTEGER);
        }

        // Filter by featured status
        $featured = $this->getState('filter.featured');
        if (is_numeric($featured)) {
            $query->where($db->quoteName('r.featured') . ' = :featured')
                  ->bind(':featured', $featured, ParameterType::INTEGER);
        }

        // Filter by rating
        $rating = $this->getState('filter.rating');
        if (!empty($rating)) {
            $query->where($db->quoteName('r.rating') . ' = :rating')
                  ->bind(':rating', $rating, ParameterType::INTEGER);
        }

        // Filter by package
        $packageId = $this->getState('filter.package_id');
        if (!empty($packageId)) {
            $query->where($db->quoteName('r.package_id') . ' = :packageId')
                  ->bind(':packageId', $packageId, ParameterType::INTEGER);
        }

        // Filter by date range
        $dateFrom = $this->getState('filter.date_from');
        if (!empty($dateFrom)) {
            $query->where($db->quoteName('r.created') . ' >= :dateFrom')
                  ->bind(':dateFrom', $dateFrom . ' 00:00:00');
        }

        $dateTo = $this->getState('filter.date_to');
        if (!empty($dateTo)) {
            $query->where($db->quoteName('r.created') . ' <= :dateTo')
                  ->bind(':dateTo', $dateTo . ' 23:59:59');
        }

        // Group by review to avoid duplicates from joins
        $query->group([
            'r.id', 'r.package_id', 'r.customer_id', 'r.booking_id', 'r.title', 'r.comment', 
            'r.rating', 'r.images', 'r.published', 'r.verified', 'r.featured', 'r.helpful_votes', 
            'r.created', 'r.modified', 'p.title', 'p.destination', 'c.first_name', 'c.last_name', 
            'c.email', 'b.booking_reference', 'b.start_date'
        ]);

        // Add the list ordering clause
        $orderCol = $this->state->get('list.ordering', 'r.id');
        $orderDirn = $this->state->get('list.direction', 'desc');

        // Handle special ordering cases
        if ($orderCol === 'customer_name') {
            $query->order('CONCAT(' . $db->quoteName('c.first_name') . ', " ", ' . $db->quoteName('c.last_name') . ') ' . $db->escape($orderDirn));
        } elseif ($orderCol === 'package_title') {
            $query->order($db->quoteName('p.title') . ' ' . $db->escape($orderDirn));
        } else {
            $query->order($db->escape($orderCol) . ' ' . $db->escape($orderDirn));
        }

        return $query;
    }

    /**
     * Get review statistics for dashboard
     *
     * @return  object  Statistics data
     */
    public function getReviewStats(): object
    {
        $db = $this->getDatabase();

        // Total reviews by status
        $query = $db->getQuery(true)
            ->select([
                'COUNT(*) as total_reviews',
                'SUM(published) as published_reviews',
                'SUM(verified) as verified_reviews',
                'SUM(featured) as featured_reviews',
                'AVG(rating) as average_rating'
            ])
            ->from($db->quoteName('#__hp_reviews'));
        $totals = $db->setQuery($query)->loadObject();

        // Reviews this month
        $query = $db->getQuery(true)
            ->select([
                'COUNT(*) as count',
                'AVG(rating) as avg_rating'
            ])
            ->from($db->quoteName('#__hp_reviews'))
            ->where($db->quoteName('created') . ' >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)');
        $thisMonth = $db->setQuery($query)->loadObject();

        // Pending reviews (unpublished)
        $query = $db->getQuery(true)
            ->select('COUNT(*)')
            ->from($db->quoteName('#__hp_reviews'))
            ->where($db->quoteName('published') . ' = 0');
        $pendingReviews = $db->setQuery($query)->loadResult();

        // Rating distribution
        $query = $db->getQuery(true)
            ->select([
                'rating',
                'COUNT(*) as count'
            ])
            ->from($db->quoteName('#__hp_reviews'))
            ->where($db->quoteName('published') . ' = 1')
            ->group('rating')
            ->order('rating DESC');
        $ratingDistribution = $db->setQuery($query)->loadObjectList('rating');

        // Top rated packages
        $query = $db->getQuery(true)
            ->select([
                'p.title',
                'AVG(r.rating) as average_rating',
                'COUNT(r.id) as review_count'
            ])
            ->from($db->quoteName('#__hp_packages', 'p'))
            ->innerJoin($db->quoteName('#__hp_reviews', 'r') . ' ON p.id = r.package_id')
            ->where($db->quoteName('r.published') . ' = 1')
            ->group('p.id')
            ->having('COUNT(r.id) >= 3')
            ->order('average_rating DESC, review_count DESC')
            ->setLimit(10);
        $topRatedPackages = $db->setQuery($query)->loadObjectList();

        // Recent reviews requiring attention
        $query = $db->getQuery(true)
            ->select([
                'r.*',
                'p.title as package_title',
                'CONCAT(c.first_name, " ", c.last_name) as customer_name'
            ])
            ->from($db->quoteName('#__hp_reviews', 'r'))
            ->leftJoin($db->quoteName('#__hp_packages', 'p') . ' ON r.package_id = p.id')
            ->leftJoin($db->quoteName('#__hp_customers', 'c') . ' ON r.customer_id = c.id')
            ->where($db->quoteName('r.published') . ' = 0')
            ->order($db->quoteName('r.created') . ' DESC')
            ->setLimit(10);
        $pendingReviewsList = $db->setQuery($query)->loadObjectList();

        // Monthly review trend (last 12 months)
        $query = $db->getQuery(true)
            ->select([
                'DATE_FORMAT(created, "%Y-%m") as month',
                'COUNT(*) as review_count',
                'AVG(rating) as avg_rating'
            ])
            ->from($db->quoteName('#__hp_reviews'))
            ->where($db->quoteName('created') . ' >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)')
            ->group('DATE_FORMAT(created, "%Y-%m")')
            ->order('month ASC');
        $monthlyTrend = $db->setQuery($query)->loadObjectList();

        return (object) [
            'total_reviews' => (int) $totals->total_reviews,
            'published_reviews' => (int) $totals->published_reviews,
            'verified_reviews' => (int) $totals->verified_reviews,
            'featured_reviews' => (int) $totals->featured_reviews,
            'average_rating' => round((float) $totals->average_rating, 2),
            'reviews_this_month' => (int) $thisMonth->count,
            'avg_rating_this_month' => round((float) $thisMonth->avg_rating, 2),
            'pending_reviews' => (int) $pendingReviews,
            'rating_distribution' => $ratingDistribution,
            'top_rated_packages' => $topRatedPackages,
            'pending_reviews_list' => $pendingReviewsList,
            'monthly_trend' => $monthlyTrend
        ];
    }

    /**
     * Get packages for filter dropdown
     *
     * @return  array  List of packages
     */
    public function getPackages(): array
    {
        $db = $this->getDatabase();
        
        $query = $db->getQuery(true)
            ->select([
                'DISTINCT p.id',
                'p.title',
                'COUNT(r.id) as review_count'
            ])
            ->from($db->quoteName('#__hp_packages', 'p'))
            ->leftJoin($db->quoteName('#__hp_reviews', 'r') . ' ON p.id = r.package_id')
            ->group('p.id')
            ->having('COUNT(r.id) > 0')
            ->order($db->quoteName('p.title') . ' ASC');

        return $db->setQuery($query)->loadObjectList();
    }

    /**
     * Moderate reviews (publish/unpublish)
     *
     * @param   array  $pks        Review IDs
     * @param   int    $published  Published state (0 or 1)
     * @return  boolean  True on success
     */
    public function moderateReviews(array $pks, int $published): bool
    {
        $db = $this->getDatabase();

        try {
            $db->transactionStart();

            foreach ($pks as $pk) {
                $query = $db->getQuery(true)
                    ->update($db->quoteName('#__hp_reviews'))
                    ->set($db->quoteName('published') . ' = :published')
                    ->set($db->quoteName('modified') . ' = NOW()')
                    ->where($db->quoteName('id') . ' = :pk')
                    ->bind(':published', $published, ParameterType::INTEGER)
                    ->bind(':pk', $pk, ParameterType::INTEGER);

                $db->setQuery($query)->execute();

                // Update package rating if publishing
                if ($published) {
                    $this->updatePackageRating($pk);
                }
            }

            $db->transactionCommit();
            return true;

        } catch (Exception $e) {
            $db->transactionRollback();
            $this->setError($e->getMessage());
            return false;
        }
    }

    /**
     * Mark reviews as verified
     *
     * @param   array  $pks  Review IDs
     * @return  boolean  True on success
     */
    public function verifyReviews(array $pks): bool
    {
        $db = $this->getDatabase();

        try {
            foreach ($pks as $pk) {
                // Check if review has valid booking
                $query = $db->getQuery(true)
                    ->select([
                        'r.booking_id',
                        'b.status',
                        'b.customer_id'
                    ])
                    ->from($db->quoteName('#__hp_reviews', 'r'))
                    ->leftJoin($db->quoteName('#__hp_bookings', 'b') . ' ON r.booking_id = b.id')
                    ->where($db->quoteName('r.id') . ' = :pk')
                    ->bind(':pk', $pk, ParameterType::INTEGER);

                $reviewData = $db->setQuery($query)->loadObject();

                if ($reviewData && $reviewData->booking_id && in_array($reviewData->status, ['completed', 'confirmed'])) {
                    $query = $db->getQuery(true)
                        ->update($db->quoteName('#__hp_reviews'))
                        ->set($db->quoteName('verified') . ' = 1')
                        ->set($db->quoteName('modified') . ' = NOW()')
                        ->where($db->quoteName('id') . ' = :pk')
                        ->bind(':pk', $pk, ParameterType::INTEGER);

                    $db->setQuery($query)->execute();
                }
            }

            return true;

        } catch (Exception $e) {
            $this->setError($e->getMessage());
            return false;
        }
    }

    /**
     * Feature/unfeature reviews
     *
     * @param   array  $pks       Review IDs
     * @param   int    $featured  Featured state (0 or 1)
     * @return  boolean  True on success
     */
    public function featureReviews(array $pks, int $featured): bool
    {
        $db = $this->getDatabase();

        try {
            foreach ($pks as $pk) {
                $query = $db->getQuery(true)
                    ->update($db->quoteName('#__hp_reviews'))
                    ->set($db->quoteName('featured') . ' = :featured')
                    ->set($db->quoteName('modified') . ' = NOW()')
                    ->where($db->quoteName('id') . ' = :pk')
                    ->bind(':featured', $featured, ParameterType::INTEGER)
                    ->bind(':pk', $pk, ParameterType::INTEGER);

                $db->setQuery($query)->execute();
            }

            return true;

        } catch (Exception $e) {
            $this->setError($e->getMessage());
            return false;
        }
    }

    /**
     * Delete reviews and their responses
     *
     * @param   array  $pks  Review IDs
     * @return  boolean  True on success
     */
    public function delete($pks)
    {
        $db = $this->getDatabase();
        $pks = (array) $pks;

        try {
            $db->transactionStart();

            foreach ($pks as $pk) {
                // Delete review responses
                $query = $db->getQuery(true)
                    ->delete($db->quoteName('#__hp_review_responses'))
                    ->where($db->quoteName('review_id') . ' = :reviewId')
                    ->bind(':reviewId', $pk, ParameterType::INTEGER);
                $db->setQuery($query)->execute();

                // Get package ID before deleting review
                $query = $db->getQuery(true)
                    ->select($db->quoteName('package_id'))
                    ->from($db->quoteName('#__hp_reviews'))
                    ->where($db->quoteName('id') . ' = :pk')
                    ->bind(':pk', $pk, ParameterType::INTEGER);
                $packageId = $db->setQuery($query)->loadResult();

                // Delete review
                $query = $db->getQuery(true)
                    ->delete($db->quoteName('#__hp_reviews'))
                    ->where($db->quoteName('id') . ' = :pk')
                    ->bind(':pk', $pk, ParameterType::INTEGER);
                $db->setQuery($query)->execute();

                // Update package rating
                if ($packageId) {
                    $this->recalculatePackageRating($packageId);
                }
            }

            $db->transactionCommit();
            return true;

        } catch (Exception $e) {
            $db->transactionRollback();
            $this->setError($e->getMessage());
            return false;
        }
    }

    /**
     * Update package rating after review changes
     *
     * @param   int  $reviewId  Review ID
     * @return  void
     */
    protected function updatePackageRating(int $reviewId): void
    {
        $db = $this->getDatabase();

        // Get package ID from review
        $query = $db->getQuery(true)
            ->select($db->quoteName('package_id'))
            ->from($db->quoteName('#__hp_reviews'))
            ->where($db->quoteName('id') . ' = :reviewId')
            ->bind(':reviewId', $reviewId, ParameterType::INTEGER);

        $packageId = $db->setQuery($query)->loadResult();

        if ($packageId) {
            $this->recalculatePackageRating($packageId);
        }
    }

    /**
     * Recalculate package rating based on published reviews
     *
     * @param   int  $packageId  Package ID
     * @return  void
     */
    protected function recalculatePackageRating(int $packageId): void
    {
        $db = $this->getDatabase();

        // Calculate average rating from published reviews
        $query = $db->getQuery(true)
            ->select([
                'AVG(rating) as avg_rating',
                'COUNT(*) as review_count'
            ])
            ->from($db->quoteName('#__hp_reviews'))
            ->where($db->quoteName('package_id') . ' = :packageId')
            ->where($db->quoteName('published') . ' = 1')
            ->bind(':packageId', $packageId, ParameterType::INTEGER);

        $ratingData = $db->setQuery($query)->loadObject();

        // Update package with new rating
        $avgRating = $ratingData->avg_rating ? round((float) $ratingData->avg_rating, 2) : 0;
        $reviewCount = (int) $ratingData->review_count;

        $query = $db->getQuery(true)
            ->update($db->quoteName('#__hp_packages'))
            ->set([
                $db->quoteName('rating') . ' = :rating',
                $db->quoteName('reviews_count') . ' = :reviewCount'
            ])
            ->where($db->quoteName('id') . ' = :packageId')
            ->bind(':rating', $avgRating, ParameterType::FLOAT)
            ->bind(':reviewCount', $reviewCount, ParameterType::INTEGER)
            ->bind(':packageId', $packageId, ParameterType::INTEGER);

        $db->setQuery($query)->execute();
    }

    /**
     * Generate review sentiment analysis report
     *
     * @param   int  $packageId  Optional package ID to analyze
     * @return  array  Sentiment analysis data
     */
    public function analyzeSentiment(?int $packageId = null): array
    {
        $db = $this->getDatabase();

        try {
            $query = $db->getQuery(true)
                ->select([
                    'r.*',
                    'p.title as package_title'
                ])
                ->from($db->quoteName('#__hp_reviews', 'r'))
                ->leftJoin($db->quoteName('#__hp_packages', 'p') . ' ON r.package_id = p.id')
                ->where($db->quoteName('r.published') . ' = 1');

            if ($packageId) {
                $query->where($db->quoteName('r.package_id') . ' = :packageId')
                      ->bind(':packageId', $packageId, ParameterType::INTEGER);
            }

            $reviews = $db->setQuery($query)->loadObjectList();

            // Basic keyword-based sentiment analysis
            $positiveWords = ['excellent', 'amazing', 'wonderful', 'great', 'fantastic', 'perfect', 'love', 'best', 'awesome', 'beautiful'];
            $negativeWords = ['terrible', 'awful', 'bad', 'worst', 'horrible', 'disappointing', 'poor', 'hate', 'disgusting'];

            $sentimentData = [];
            $totalPositive = 0;
            $totalNegative = 0;
            $totalNeutral = 0;

            foreach ($reviews as $review) {
                $text = strtolower($review->comment . ' ' . $review->title);
                $positiveScore = 0;
                $negativeScore = 0;

                foreach ($positiveWords as $word) {
                    $positiveScore += substr_count($text, $word);
                }

                foreach ($negativeWords as $word) {
                    $negativeScore += substr_count($text, $word);
                }

                // Determine sentiment
                if ($positiveScore > $negativeScore) {
                    $sentiment = 'positive';
                    $totalPositive++;
                } elseif ($negativeScore > $positiveScore) {
                    $sentiment = 'negative';
                    $totalNegative++;
                } else {
                    $sentiment = 'neutral';
                    $totalNeutral++;
                }

                $sentimentData[] = [
                    'review_id' => $review->id,
                    'package_title' => $review->package_title,
                    'rating' => $review->rating,
                    'sentiment' => $sentiment,
                    'positive_score' => $positiveScore,
                    'negative_score' => $negativeScore
                ];
            }

            return [
                'success' => true,
                'total_reviews' => count($reviews),
                'sentiment_summary' => [
                    'positive' => $totalPositive,
                    'negative' => $totalNegative,
                    'neutral' => $totalNeutral
                ],
                'detailed_analysis' => $sentimentData
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => Text::sprintf('COM_HOLIDAYPACKAGES_ERROR_SENTIMENT_ANALYSIS', $e->getMessage())
            ];
        }
    }
}